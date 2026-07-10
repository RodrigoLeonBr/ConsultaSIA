<?php

namespace Tests\Unit;

use App\Services\AihTextImportService;
use Tests\TestCase;

class AihTextImportServiceTest extends TestCase
{
    public function test_parse_aih_file_allows_reopen_episodes_in_same_competencia(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'haih_');
        file_put_contents($path, implode("\n", [
            '3519108004643;1;2081733;202303;3550308;19600113;59;M;20190116;20230228;01;05;0303170204;F09 ;    ;02;06;0000;23;    ;28;0;1849,4',
            '3519108004643;1;2081733;202303;3550308;19600113;59;M;20190116;20230331;01;05;0303170204;F09 ;    ;02;06;0000;23;    ;31;0;2047,54',
        ]));

        try {
            $records = (new AihTextImportService)->parseAihFile($path);
        } finally {
            unlink($path);
        }

        $this->assertCount(2, $records);

        $keys = array_map(
            fn (array $r) => $r['AIH'].'|'.$r['CNES'].'|'.$r['COMPETENCIA'].'|'.$r['DT_SAIDA'],
            $records
        );

        $this->assertCount(2, array_unique($keys));
        $this->assertSame('20230228', $records[0]['DT_SAIDA']);
        $this->assertSame('20230331', $records[1]['DT_SAIDA']);
        $this->assertSame('1', $records[0]['IDENT_AIH']);
        $this->assertSame('3550308', $records[0]['MUN_RESIDENCIA']);
        $this->assertSame('01', $records[0]['CARATER_INTERNACAO']);
        $this->assertSame('', $records[0]['DIAG_SECUNDARIO']);
        $this->assertSame('', $records[0]['CID_OBITO']);
    }

    public function test_parse_aih_file_without_diag_secundario_column(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'haih_');
        file_put_contents($path, '3525129193971;01;2082179;202601;350160;19800626;45;M;20251207;20251207;01;01;0407040102;K409;02;06;0000;12;0000;1;0;637,97');

        try {
            $records = (new AihTextImportService)->parseAihFile($path);
        } finally {
            unlink($path);
        }

        $this->assertCount(1, $records);
        $this->assertSame('3525129193971', $records[0]['AIH']);
        $this->assertSame('01', $records[0]['IDENT_AIH']);
        $this->assertSame('350160', $records[0]['MUN_RESIDENCIA']);
        $this->assertSame('K409', $records[0]['DIAG_PRINCIPAL']);
        $this->assertSame('', $records[0]['DIAG_SECUNDARIO']);
        $this->assertSame('02', $records[0]['COMPLEXIDADE']);
        $this->assertSame('0000', $records[0]['CID_OBITO']);
        $this->assertSame(637.97, $records[0]['VALOR_TOTAL_AIH']);
    }

    public function test_unique_keys_for_sample_reopen_cases(): void
    {
        $cases = [
            ['3519108004643', '2081733', '202303', '20230228'],
            ['3519108004643', '2081733', '202303', '20230331'],
            ['3522106471312', '2081733', '202204', '20220331'],
            ['3522106471312', '2081733', '202204', '20220423'],
        ];

        $keys = array_map(
            fn (array $c) => implode('|', $c),
            $cases
        );

        $this->assertCount(4, array_unique($keys));
    }
}
