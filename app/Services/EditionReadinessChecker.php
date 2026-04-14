<?php

namespace App\Services;

use App\Models\ContentNode;
use App\Models\ContentNodeTranslation;
use App\Models\Document;
use App\Models\Edition;
use App\Models\Law;
use App\Models\LawQa;
use App\Models\LawTranslation;
use App\Models\ChangelogEntry;
use Illuminate\Support\Collection;

class EditionReadinessChecker
{
    /**
     * @return array{
     *     is_ready: bool,
     *     overall_status: string,
     *     blocking_count: int,
     *     warning_count: int,
     *     passed_count: int,
     *     info_count: int,
     *     summary: string,
     *     readiness_note: string,
     *     checks: array<int, array{
     *         status: string,
     *         label: string,
     *         summary: string,
     *         details: array<int, string>,
     *         issue_count: int
     *     }>
     * }
     */
    public function check(Edition $edition): array
    {
        $edition->loadMissing([
            'laws.translations',
            'laws.contentNodes.translations',
        ]);

        $checks = [
            $this->checkLawCoverage($edition),
            $this->checkLawContent($edition),
            $this->checkTranslations($edition),
            $this->checkNodeTree($edition),
            $this->checkRequiredDocuments(),
            $this->checkQas($edition),
            $this->checkChangelogEntries($edition),
        ];

        $blockingCount = collect($checks)->sum(fn (array $check) => $check['status'] === 'fail' ? $check['issue_count'] : 0);
        $warningCount = collect($checks)->sum(fn (array $check) => $check['status'] === 'warn' ? $check['issue_count'] : 0);
        $passedCount = collect($checks)->where('status', 'pass')->count();
        $infoCount = collect($checks)->where('status', 'info')->count();
        $isReady = $blockingCount === 0;
        $overallStatus = $blockingCount > 0 ? 'fail' : ($warningCount > 0 ? 'warn' : 'pass');

        $headlineChecks = collect($checks)
            ->filter(fn (array $check) => in_array($check['status'], ['fail', 'warn'], true))
            ->take(3)
            ->map(fn (array $check) => $check['label'])
            ->values()
            ->all();

        $summary = match (true) {
            $blockingCount > 0 => $blockingCount.' blocking '.str($blockingCount === 1 ? 'issue' : 'issues')->value()
                .($warningCount > 0 ? ' + '.$warningCount.' warning'.($warningCount === 1 ? '' : 's') : '')
                .(count($headlineChecks) > 0 ? ' - '.implode(', ', $headlineChecks) : ''),
            $warningCount > 0 => $warningCount.' non-blocking warning'.($warningCount === 1 ? '' : 's')
                .(count($headlineChecks) > 0 ? ' - '.implode(', ', $headlineChecks) : ''),
            default => 'Complete. '.($passedCount > 0 ? $passedCount.' checks passed.' : 'No blocking issues found.'),
        };

        return [
            'is_ready' => $isReady,
            'overall_status' => $overallStatus,
            'blocking_count' => $blockingCount,
            'warning_count' => $warningCount,
            'passed_count' => $passedCount,
            'info_count' => $infoCount,
            'summary' => $summary,
            'readiness_note' => $isReady
                ? 'This edition is ready to be published or activated.'
                : 'This edition still has blocking completeness issues before it can be published or activated.',
            'checks' => $checks,
        ];
    }

    /**
     * @return array{status: string, label: string, summary: string, details: array<int, string>, issue_count: int}
     */
    protected function checkLawCoverage(Edition $edition): array
    {
        $expectedCount = max((int) config('lotg.expected_law_count', 17), 1);
        $expectedNumbers = collect(range(1, $expectedCount))->map(fn (int $number) => (string) $number);

        $laws = $edition->laws;
        $nonNumericNumbers = $laws
            ->reject(fn (Law $law) => is_numeric($law->law_number))
            ->map(fn (Law $law) => (string) $law->law_number)
            ->filter()
            ->values();
        $numericLaws = $laws->filter(fn (Law $law) => is_numeric($law->law_number));
        $presentNumbers = $numericLaws
            ->map(fn (Law $law) => (string) ((int) $law->law_number))
            ->values();

        $duplicateNumbers = $presentNumbers
            ->countBy()
            ->filter(fn (int $count) => $count > 1)
            ->keys()
            ->values();

        $missingNumbers = $expectedNumbers->diff($presentNumbers->unique())->values();
        $outOfRangeNumbers = $numericLaws
            ->filter(fn (Law $law) => ((int) $law->law_number) < 1 || ((int) $law->law_number) > $expectedCount)
            ->map(fn (Law $law) => (string) $law->law_number)
            ->values();

        $details = [];

        if ($missingNumbers->isNotEmpty()) {
            $details[] = 'Missing laws: '.$missingNumbers->implode(', ');
        }

        if ($duplicateNumbers->isNotEmpty()) {
            $details[] = 'Duplicate law numbers: '.$duplicateNumbers->implode(', ');
        }

        if ($outOfRangeNumbers->isNotEmpty()) {
            $details[] = 'Out-of-range law numbers: '.$outOfRangeNumbers->implode(', ');
        }

        if ($nonNumericNumbers->isNotEmpty()) {
            $details[] = 'Non-numeric law numbers: '.$nonNumericNumbers->implode(', ');
        }

        if (empty($details)) {
            return $this->makeCheck(
                status: 'pass',
                label: 'All laws exist (1-'.$expectedCount.')',
                summary: 'All expected law numbers are present.',
            );
        }

        return $this->makeCheck(
            status: 'fail',
            label: 'All laws exist (1-'.$expectedCount.')',
            summary: 'Found '.$presentNumbers->unique()->count().' of '.$expectedCount.' expected laws.',
            details: $details,
            issueCount: $missingNumbers->count() + $duplicateNumbers->count() + $outOfRangeNumbers->count() + $nonNumericNumbers->count(),
        );
    }

    /**
     * @return array{status: string, label: string, summary: string, details: array<int, string>, issue_count: int}
     */
    protected function checkLawContent(Edition $edition): array
    {
        $emptyLaws = $edition->laws
            ->filter(fn (Law $law) => $law->contentNodes->isEmpty())
            ->map(fn (Law $law) => $this->lawLabel($law))
            ->values();

        if ($emptyLaws->isEmpty()) {
            return $this->makeCheck(
                status: 'pass',
                label: 'Each law has content',
                summary: 'Every law in this edition has at least one content node.',
            );
        }

        return $this->makeCheck(
            status: 'fail',
            label: 'Each law has content',
            summary: $emptyLaws->count().' '.str($emptyLaws->count() === 1 ? 'law has' : 'laws have')->value().' no content nodes.',
            details: $emptyLaws->map(fn (string $label) => $label.' has no content nodes.')->all(),
            issueCount: $emptyLaws->count(),
        );
    }

    /**
     * @return array{status: string, label: string, summary: string, details: array<int, string>, issue_count: int}
     */
    protected function checkTranslations(Edition $edition): array
    {
        $missingLawTitlesId = [];
        $missingLawTitlesEn = [];
        $missingNodeContentId = [];
        $missingNodeContentEn = [];

        foreach ($edition->laws as $law) {
            /** @var LawTranslation|null $lawIdTranslation */
            $lawIdTranslation = $law->translations->firstWhere('language_code', 'id');
            /** @var LawTranslation|null $lawEnTranslation */
            $lawEnTranslation = $law->translations->firstWhere('language_code', 'en');

            if (! filled($lawIdTranslation?->title)) {
                $missingLawTitlesId[] = $this->lawLabel($law);
            }

            if (! filled($lawEnTranslation?->title)) {
                $missingLawTitlesEn[] = $this->lawLabel($law);
            }

            foreach ($law->contentNodes as $node) {
                if (! $this->nodeRequiresTranslatedContent($node)) {
                    continue;
                }

                /** @var ContentNodeTranslation|null $nodeIdTranslation */
                $nodeIdTranslation = $node->translations->firstWhere('language_code', 'id');
                /** @var ContentNodeTranslation|null $nodeEnTranslation */
                $nodeEnTranslation = $node->translations->firstWhere('language_code', 'en');

                if (! $this->nodeTranslationHasRequiredContent($node, $nodeIdTranslation)) {
                    $missingNodeContentId[] = $this->nodeLabel($law, $node);
                }

                if (! $this->nodeTranslationHasRequiredContent($node, $nodeEnTranslation)) {
                    $missingNodeContentEn[] = $this->nodeLabel($law, $node);
                }
            }
        }

        $details = [];

        if (! empty($missingLawTitlesId)) {
            $details[] = 'Missing Indonesian law titles: '.implode(', ', $missingLawTitlesId);
        }

        if (! empty($missingNodeContentId)) {
            $details[] = 'Missing Indonesian required node content: '.implode(' | ', $missingNodeContentId);
        }

        if (! empty($missingLawTitlesEn)) {
            $details[] = 'Missing English law titles: '.implode(', ', $missingLawTitlesEn);
        }

        if (! empty($missingNodeContentEn)) {
            $details[] = 'Missing English required node content: '.implode(' | ', $missingNodeContentEn);
        }

        $blockingCount = count($missingLawTitlesId) + count($missingNodeContentId);
        $warningCount = count($missingLawTitlesEn) + count($missingNodeContentEn);

        if ($blockingCount === 0 && $warningCount === 0) {
            return $this->makeCheck(
                status: 'pass',
                label: 'Translations',
                summary: 'Required Indonesian and English translation coverage looks complete.',
            );
        }

        return $this->makeCheck(
            status: $blockingCount > 0 ? 'fail' : 'warn',
            label: 'Translations',
            summary: $blockingCount > 0
                ? 'Indonesian translation gaps need to be fixed.'
                : 'English translation gaps are still present.',
            details: $details,
            issueCount: $blockingCount + $warningCount,
        );
    }

    /**
     * @return array{status: string, label: string, summary: string, details: array<int, string>, issue_count: int}
     */
    protected function checkNodeTree(Edition $edition): array
    {
        $issues = [];

        foreach ($edition->laws as $law) {
            $nodesById = $law->contentNodes->keyBy('id');

            foreach ($law->contentNodes as $node) {
                if ($node->parent_id === null) {
                    continue;
                }

                if ((int) $node->parent_id === (int) $node->id) {
                    $issues[] = $this->lawLabel($law).' / Node #'.$node->id.' cannot be its own parent.';
                    continue;
                }

                if (! $nodesById->has($node->parent_id)) {
                    $issues[] = $this->lawLabel($law).' / Node #'.$node->id.' points to missing parent #'.$node->parent_id.'.';
                }
            }

            foreach ($law->contentNodes as $node) {
                $visited = [];
                $currentNode = $node;

                while ($currentNode->parent_id !== null) {
                    if (isset($visited[$currentNode->id])) {
                        $issues[] = $this->lawLabel($law).' has a cyclic node chain involving node #'.$currentNode->id.'.';
                        break;
                    }

                    $visited[$currentNode->id] = true;
                    $parentNode = $nodesById->get($currentNode->parent_id);

                    if (! $parentNode instanceof ContentNode) {
                        break;
                    }

                    $currentNode = $parentNode;
                }
            }

            foreach ($law->contentNodes->groupBy(fn (ContentNode $node) => $node->parent_id ?? 'root') as $parentId => $siblings) {
                $actualOrders = $siblings
                    ->sortBy('sort_order')
                    ->pluck('sort_order')
                    ->map(fn ($sortOrder) => (int) $sortOrder)
                    ->values()
                    ->all();

                $expectedOrders = range(1, count($actualOrders));

                if ($actualOrders !== $expectedOrders) {
                    $location = $parentId === 'root'
                        ? 'root level'
                        : 'children of node #'.$parentId;

                    $issues[] = $this->lawLabel($law).' has broken sibling sort order at '.$location.' (found '.implode(', ', $actualOrders).').';
                }
            }
        }

        $issues = array_values(array_unique($issues));

        if (empty($issues)) {
            return $this->makeCheck(
                status: 'pass',
                label: 'No broken node tree',
                summary: 'Parent links and sibling sort orders are consistent.',
            );
        }

        return $this->makeCheck(
            status: 'fail',
            label: 'No broken node tree',
            summary: count($issues).' node tree '.str(count($issues) === 1 ? 'issue was' : 'issues were')->value().' found.',
            details: $issues,
            issueCount: count($issues),
        );
    }

    /**
     * @return array{status: string, label: string, summary: string, details: array<int, string>, issue_count: int}
     */
    protected function checkRequiredDocuments(): array
    {
        $requiredSlugs = collect(config('lotg.required_document_slugs', []))
            ->filter(fn ($slug) => is_string($slug) && $slug !== '')
            ->values();

        if ($requiredSlugs->isEmpty()) {
            return $this->makeCheck(
                status: 'info',
                label: 'Required documents',
                summary: 'No required document slugs are configured.',
            );
        }

        $documents = Document::query()
            ->with('publishedPages')
            ->whereIn('slug', $requiredSlugs)
            ->get()
            ->keyBy('slug');

        $issues = [];

        foreach ($requiredSlugs as $slug) {
            $document = $documents->get($slug);

            if (! $document instanceof Document) {
                $issues[] = 'Missing required document: '.$slug;
                continue;
            }

            if ($document->status !== 'published') {
                $issues[] = 'Required document is not published: '.$document->title.' ('.$slug.')';
                continue;
            }

            if ($document->publishedPages->isEmpty()) {
                $issues[] = 'Required document has no published pages: '.$document->title.' ('.$slug.')';
            }
        }

        if (empty($issues)) {
            return $this->makeCheck(
                status: 'pass',
                label: 'Required documents',
                summary: 'All configured required documents are available and published.',
            );
        }

        return $this->makeCheck(
            status: 'fail',
            label: 'Required documents',
            summary: count($issues).' required document '.str(count($issues) === 1 ? 'issue was' : 'issues were')->value().' found.',
            details: $issues,
            issueCount: count($issues),
        );
    }

    /**
     * @return array{status: string, label: string, summary: string, details: array<int, string>, issue_count: int}
     */
    protected function checkQas(Edition $edition): array
    {
        $lawIds = $edition->laws->pluck('id')->all();

        if (empty($lawIds)) {
            return $this->makeCheck(
                status: 'info',
                label: 'Q&A coverage',
                summary: 'No laws exist yet, so Q&A coverage is not checked.',
            );
        }

        $publishedQasCount = LawQa::query()
            ->published()
            ->whereIn('law_id', $lawIds)
            ->count();

        if ($publishedQasCount > 0) {
            return $this->makeCheck(
                status: 'pass',
                label: 'Q&A coverage',
                summary: $publishedQasCount.' published Q&A '.str($publishedQasCount === 1 ? 'item is' : 'items are')->value().' available for this edition.',
            );
        }

        return $this->makeCheck(
            status: 'warn',
            label: 'Q&A coverage',
            summary: 'No published Q&A items exist for this edition yet.',
            details: ['This is non-blocking, but the public Q&A area for the current edition will be empty.'],
            issueCount: 1,
        );
    }

    /**
     * @return array{status: string, label: string, summary: string, details: array<int, string>, issue_count: int}
     */
    protected function checkChangelogEntries(Edition $edition): array
    {
        $publishedEntriesCount = ChangelogEntry::query()
            ->published()
            ->where('edition_id', $edition->id)
            ->count();

        if ($publishedEntriesCount > 0) {
            return $this->makeCheck(
                status: 'pass',
                label: 'Edition changes',
                summary: $publishedEntriesCount.' published edition change '.str($publishedEntriesCount === 1 ? 'entry is' : 'entries are')->value().' available.',
            );
        }

        return $this->makeCheck(
            status: 'warn',
            label: 'Edition changes',
            summary: 'No published edition change entries exist yet.',
            details: ['This is non-blocking, but the Law Changes page for this edition will be empty.'],
            issueCount: 1,
        );
    }

    protected function nodeRequiresTranslatedContent(ContentNode $node): bool
    {
        return in_array($node->node_type, ['section', 'rich_text'], true);
    }

    protected function nodeTranslationHasRequiredContent(ContentNode $node, ?ContentNodeTranslation $translation): bool
    {
        if (! $translation) {
            return false;
        }

        return match ($node->node_type) {
            'section' => filled($translation->title),
            'rich_text' => filled($translation->body_html),
            default => true,
        };
    }

    protected function lawLabel(Law $law): string
    {
        return 'Law '.$law->law_number;
    }

    protected function nodeLabel(Law $law, ContentNode $node): string
    {
        $title = $node->translations->firstWhere('language_code', 'id')?->title;
        $suffix = $title ? ' ('.$title.')' : '';

        return $this->lawLabel($law).' / Node #'.$node->id.' ['.$node->node_type.']'.$suffix;
    }

    /**
     * @return array{status: string, label: string, summary: string, details: array<int, string>, issue_count: int}
     */
    protected function makeCheck(
        string $status,
        string $label,
        string $summary,
        array $details = [],
        ?int $issueCount = null
    ): array {
        return [
            'status' => $status,
            'label' => $label,
            'summary' => $summary,
            'details' => $details,
            'issue_count' => $issueCount ?? (($status === 'fail' || $status === 'warn') ? max(count($details), 1) : 0),
        ];
    }
}
