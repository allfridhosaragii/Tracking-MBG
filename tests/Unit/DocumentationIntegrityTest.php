<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class DocumentationIntegrityTest extends TestCase
{
    /**
     * Verify that all documentation files match their baseline SHA-256 hash.
     */
    public function test_all_documentation_files_match_baseline_sha256(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $manifestPath = $projectRoot . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'documentation_sha256_manifest.json';
        $this->assertFileExists($manifestPath, 'Manifest hash harus ada.');

        $manifest = json_decode(file_get_contents($manifestPath), true);
        $this->assertIsArray($manifest);
        $this->assertCount(76, $manifest, 'Baseline harus memuat tepat 76 berkas dokumentasi.');

        $docsRoot = $projectRoot . DIRECTORY_SEPARATOR . 'MBG-Track-Documentation';

        foreach ($manifest as $entry) {
            $filePath = $docsRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $entry['Path']);
            $this->assertFileExists($filePath, "Berkas dokumentasi {$entry['Path']} harus ada.");

            $currentHash = strtoupper(hash_file('sha256', $filePath));
            $this->assertSame(
                strtoupper($entry['SHA256']),
                $currentHash,
                "Integritas berkas {$entry['Path']} gagal! Hash tidak cocok dengan baseline."
            );
        }
    }
}
