<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VisaLookupController extends Controller
{
    private const EVISA_BASE = 'https://evisa.mae.ro/Dosar/Cautare';

    public function lookup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'regex:/^[A-Z0-9]{5,30}$/', 'max:30'],
        ]);

        $code = strtoupper(trim($data['code']));

        try {
            $response = Http::withOptions(['verify' => true])
                ->timeout(15)
                ->withHeaders(['Accept-Language' => 'en-US,en;q=0.9'])
                ->get(self::EVISA_BASE, [
                    'CodDosar' => $code,
                    'lang' => 'en-US',
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json(['error' => 'Could not reach the visa portal. Please try again.'], 502);
        }

        if (! $response->successful()) {
            return response()->json(['error' => 'Visa portal returned an unexpected response.'], 502);
        }

        $html = $response->body();
        $parsed = $this->parseStatusPage($html);

        if ($parsed === null) {
            return response()->json(['error' => 'Case not found or portal response could not be parsed.'], 404);
        }

        return response()->json(array_merge(['code' => $code], $parsed));
    }

    private function parseStatusPage(string $html): ?array
    {
        $doc = new \DOMDocument();
        @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($doc);

        // The portal renders a table or dl with application details.
        // Extract all visible text pairs (label => value) from the result section.
        $result = [];

        // Try table rows first (th/td pairs)
        $rows = $xpath->query('//table//tr');
        foreach ($rows as $row) {
            $cells = $xpath->query('td|th', $row);
            if ($cells->length >= 2) {
                $label = trim($cells->item(0)->textContent ?? '');
                $value = trim($cells->item(1)->textContent ?? '');
                if ($label !== '' && $value !== '') {
                    $result[$label] = $value;
                }
            }
        }

        // Also try definition lists (dt/dd)
        $dts = $xpath->query('//dl/dt');
        foreach ($dts as $dt) {
            $dd = $dt->nextSibling;
            while ($dd && $dd->nodeName === '#text') {
                $dd = $dd->nextSibling;
            }
            if ($dd && $dd->nodeName === 'dd') {
                $label = trim($dt->textContent ?? '');
                $value = trim($dd->textContent ?? '');
                if ($label !== '' && $value !== '') {
                    $result[$label] = $value;
                }
            }
        }

        // Heuristic: if we got nothing, check for a "not found" message
        if (empty($result)) {
            $bodyText = strtolower(strip_tags($html));
            if (str_contains($bodyText, 'not found') || str_contains($bodyText, 'nu a fost gasit') || str_contains($bodyText, 'invalid')) {
                return null;
            }
            // Return raw text snippet so at least something is surfaced
            $snippet = substr(trim(preg_replace('/\s+/', ' ', strip_tags($html))), 0, 500);
            if ($snippet === '') {
                return null;
            }
            return ['raw' => $snippet];
        }

        return ['fields' => $result];
    }
}
