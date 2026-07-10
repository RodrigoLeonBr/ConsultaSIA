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
            '3519108004643;2081733;202303;19600113;59;M;20190116;20230228;05;0303170204;F09 ;02;06;0000;23;28;0;1849,4',
            '3519108004643;2081733;202303;19600113;59;M;20190116;20230331;05;0303170204;F09 ;02;06;0000;23;31;0;2047,54',
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
