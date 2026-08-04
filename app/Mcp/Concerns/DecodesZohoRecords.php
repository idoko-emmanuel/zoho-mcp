<?php

namespace App\Mcp\Concerns;

/**
 * Zoho Sprints returns collections as `{ "<key>JObj": { id: [v0, v1, …] }, "<key>_prop": { field: index } }`
 * — positional rows plus a name→index map. These helpers turn that into named arrays so
 * tools can hand a model something readable instead of an array of unlabelled values.
 */
trait DecodesZohoRecords
{
    /**
     * @param  array<string, mixed>  $raw  the decoded API response
     * @param  string  $recordsKey  e.g. "statusJObj"
     * @param  string  $propKey  e.g. "status_prop"
     * @param  array<string, string>  $fields  output name => Zoho field name
     * @return list<array<string, mixed>> each row prefixed with its `id`
     */
    protected function decodeRecords(array $raw, string $recordsKey, string $propKey, array $fields): array
    {
        $prop = $raw[$propKey] ?? [];

        $records = [];
        foreach ($raw[$recordsKey] ?? [] as $id => $row) {
            $record = ['id' => (string) $id];
            foreach ($fields as $out => $zohoField) {
                $record[$out] = $this->field((array) $row, $prop, $zohoField);
            }
            $records[] = $record;
        }

        return $records;
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<string, int>  $prop
     */
    protected function field(array $row, array $prop, string $name): mixed
    {
        $index = $prop[$name] ?? null;

        return $index === null ? null : ($row[$index] ?? null);
    }
}
