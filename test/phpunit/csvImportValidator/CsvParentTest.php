<?php

use org\bovigo\vfs\vfsStream;

/**
 * @internal
 * @covers \csvParentIdTest
 */
class CsvParentTest extends \PHPUnit\Framework\TestCase
{
    protected $vdbcon;
    protected $context;

    public function setUp(): void
    {
        $this->context = sfContext::getInstance();
        $this->vdbcon = $this->createMock(DebugPDO::class);

        $this->csvHeader = 'legacyId,parentId,identifier,title,levelOfDescription,extentAndMedium,repository,culture';
        $this->csvHeaderMissingParentId = 'legacyId,identifier,title,levelOfDescription,extentAndMedium,repository,culture';
        $this->csvHeaderMissingLegacyId = 'parentId,identifier,title,levelOfDescription,extentAndMedium,repository,culture';
        $this->csvHeaderMissingParentIdLegacyId = 'identifier,title,levelOfDescription,extentAndMedium,repository,culture';
        $this->csvHeaderWithQubitParentSlug = 'legacyId,qubitParentSlug,identifier,title,levelOfDescription,extentAndMedium,repository,culture';
        $this->csvHeaderWithParentIdQubitParentSlug = 'legacyId,parentId,qubitParentSlug,identifier,title,levelOfDescription,extentAndMedium,repository,culture';

        $this->csvData = [
            // Note: leading and trailing whitespace in first row is intentional
            '"B10101 "," DJ001","ID1 ","Some Photographs","","Extent and medium 1","",""',
            '"","","","Chemise","","","","fr"',
            '"D20202", "DJ002", "", "Voûte, étagère 0074", "", "", "", ""',
            '"", "DJ003", "ID4", "Title Four", "","", "", "en"',
        ];

        $this->csvDataMissingParentId = [
            '"B10101 ","ID1 ","Some Photographs","","Extent and medium 1","",""',
            '"","","Chemise","","","","fr"',
            '"D20202", "", "Voûte, étagère 0074", "", "", "", ""',
            '"", "ID4", "Title Four", "","", "", "en"',
        ];

        $this->csvDataMissingLegacyId = [
            '" DJ001","ID1 ","Some Photographs","","Extent and medium 1","",""',
            '"","","Chemise","","","","fr"',
            '"DJ002", "", "Voûte, étagère 0074", "", "", "", ""',
            '"DJ003", "ID4", "Title Four", "","", "", "en"',
        ];

        $this->csvDataMissingParentIdLegacyId = [
            '"ID1 ","Some Photographs","","Extent and medium 1","",""',
            '"","Chemise","","","","fr"',
            '"", "Voûte, étagère 0074", "", "", "", ""',
            '"ID4", "Title Four", "","", "", "en"',
        ];

        $this->csvDataParentIdColumnEmpty = [
            '"B10101 "," ","ID1 ","Some Photographs","","Extent and medium 1","",""',
            '"","","","Chemise","","","","fr"',
            '"D20202", "", "", "Voûte, étagère 0074", "", "", "", ""',
            '"X7", "", "ID4", "Title Four", "","", "", "en"',
        ];

        $this->csvDataParentIdMatches = [
            '"B10101 "," ","ID1 ","Some Photographs","","Extent and medium 1","",""',
            '"","","","Chemise","","","","fr"',
            '"D20202", "B10101 ", "", "Voûte, étagère 0074", "", "", "", ""',
            '"X7", "", "ID4", "Title Four", "","", "", "en"',
        ];

        $this->csvDataParentIdMatchesInKeymap = [
            '"B10101 "," ","ID1 ","Some Photographs","","Extent and medium 1","",""',
            '"","","","Chemise","","","","fr"',
            '"D20202", "A10101 ", "", "Voûte, étagère 0074", "", "", "", ""',
            '"X7", "", "ID4", "Title Four", "","", "", "en"',
        ];

        $this->csvDataQubitParentSlug = [
            '"B10101 "," ","ID1 ","Some Photographs","","Extent and medium 1","",""',
            '"C10101","","","Chemise","","","","fr"',
            '"D20202", "parent-slug", "", "Voûte, étagère 0074", "", "", "", ""',
            '"X7", "", "ID4", "Title Four", "","", "", "en"',
            '"X7", "missing-slug", "TY99", "Some stuff", "","", "", "en"',
        ];

        $this->csvDataParentIdAndQubitParentSlug = [
            '"B10101 ", "", " ","ID1 ","Some Photographs","","Extent and medium 1","",""',
            '"C10101","B10101","","","Chemise","","","","fr"',
            '"D20202", "C10101", "parent-slug", "", "Voûte, étagère 0074", "", "", "", ""',
            '"X7","", "parent-slug-again", "ID4", "Title Four", "","", "", "en"',
        ];

        // define virtual file system
        $directory = [
            'unix_csv_without_utf8_bom.csv' => $this->csvHeader."\n".implode("\n", $this->csvData),

            'unix_csv_missing_parent_id.csv' => $this->csvHeaderMissingParentId."\n".implode("\n", $this->csvDataMissingParentId),
            'unix_csv_missing_legacy_id.csv' => $this->csvHeaderMissingLegacyId."\n".implode("\n", $this->csvDataMissingLegacyId),
            'unix_csv_missing_parent_id_legacy_id.csv' => $this->csvHeaderMissingParentIdLegacyId."\n".implode("\n", $this->csvDataMissingParentIdLegacyId),
            'unix_csv_parent_id_column_empty.csv' => $this->csvHeader."\n".implode("\n", $this->csvDataParentIdColumnEmpty),
            'unix_csv_parent_id_matches.csv' => $this->csvHeader."\n".implode("\n", $this->csvDataParentIdMatches),
            'unix_csv_parent_id_matches_in_keymap.csv' => $this->csvHeader."\n".implode("\n", $this->csvDataParentIdMatchesInKeymap),
            'unix_csv_qubit_parent_slug.csv' => $this->csvHeaderWithQubitParentSlug."\n".implode("\n", $this->csvDataQubitParentSlug),
            'unix_csv_parent_id_and_qubit_parent_slug.csv' => $this->csvHeaderWithParentIdQubitParentSlug."\n".implode("\n", $this->csvDataParentIdAndQubitParentSlug),
        ];

        $this->vfs = vfsStream::setup('root', null, $directory);

        $this->ormClasses = [
            'QubitFlatfileImport' => \AccessToMemory\test\mock\QubitFlatfileImport::class,
            'QubitObject' => \AccessToMemory\test\mock\QubitObject::class,
        ];
    }

    /**
     * @dataProvider csvValidatorTestProvider
     *
     * Generic test - options and expected results from csvValidatorTestProvider()
     *
     * @param mixed $options
     */
    public function testCsvValidator($options)
    {
        $filename = $this->vfs->url().$options['filename'];
        $validatorOptions = isset($options['validatorOptions']) ? $options['validatorOptions'] : null;

        $csvValidator = new CsvImportValidator($this->context, null, $validatorOptions);
        $this->runValidator($csvValidator, $filename, $options['csvValidatorClasses']);
        $result = $csvValidator->getResultsByFilenameTestname($filename, $options['testname']);

        $this->assertSame($options[CsvValidatorResult::TEST_TITLE], $result[CsvValidatorResult::TEST_TITLE]);
        $this->assertSame($options[CsvValidatorResult::TEST_STATUS], $result[CsvValidatorResult::TEST_STATUS]);
        $this->assertSame($options[CsvValidatorResult::TEST_RESULTS], $result[CsvValidatorResult::TEST_RESULTS]);
        $this->assertSame($options[CsvValidatorResult::TEST_DETAIL], $result[CsvValidatorResult::TEST_DETAIL]);
    }

    public function csvValidatorTestProvider()
    {
        $vfsUrl = 'vfs://root';

        return [
            /*
             * Test CsvParentValidator.class.php
             *
             * Tests:
             * - parentId col missing
             * - legacyId col missing
             * - parentId not populated
             * - parentId populated - matches legacyId in file - source option populated
             * - parentId populated - matches legacyId in file - source field not populated
             * - parentId populated - matches in keymap table - source option populated
             * - parentId populated - matches in keymap table - source field not populated
             * - parentId populated - no match
             * - qubitParentSlug not populated
             * - qubitParentSlug populated - no match
             * - qubitParentSlug populated - matches db
             * - parentId and qubitParentSlug not populated
             * - parentId and qubitParentSlug both populated and matching
             * - parentId and qubitParentSlug both populated no match
             */
            [
                'CsvParentValidator-ParentIdColumnMissing' => [
                    'csvValidatorClasses' => ['CsvParentValidator' => CsvParentValidator::class],
                    'filename' => '/unix_csv_missing_parent_id.csv',
                    'testname' => 'CsvParentValidator',
                    'validatorOptions' => [
                        'verbose' => true,
                    ],
                    CsvValidatorResult::TEST_TITLE => CsvParentValidator::TITLE,
                    CsvValidatorResult::TEST_STATUS => CsvValidatorResult::RESULT_WARN,
                    CsvValidatorResult::TEST_RESULTS => [
                        "'parentId' and 'qubitParentSlugColumnPresent' columns not present. CSV contents will be imported as top level records.",
                    ],
                    CsvValidatorResult::TEST_DETAIL => [
                    ],
                ],
            ],

            [
                'CsvParentValidator-LegacyIdColumnMissing' => [
                    'csvValidatorClasses' => ['CsvParentValidator' => CsvParentValidator::class],
                    'filename' => '/unix_csv_missing_legacy_id.csv',
                    'testname' => 'CsvParentValidator',
                    'validatorOptions' => [
                        'verbose' => true,
                    ],
                    CsvValidatorResult::TEST_TITLE => CsvParentValidator::TITLE,
                    CsvValidatorResult::TEST_STATUS => CsvValidatorResult::RESULT_ERROR,
                    CsvValidatorResult::TEST_RESULTS => [
                        'Rows with parentId populated: 3',
                        '\'legacyId\' column not found. Unable to match parentId to CSV rows.',
                        "'source' option not specified. Unable to check parentId values against AtoM's database.",
                        'Number of rows for which parents could not be found (will import as top level records): 3',
                    ],
                    CsvValidatorResult::TEST_DETAIL => [
                        'DJ001,ID1,Some Photographs,,Extent and medium 1,,',
                        'DJ002,,Voûte, étagère 0074,,,,',
                        'DJ003,ID4,Title Four,,,,en',
                    ],
                ],
            ],

            [
                'CsvParentValidator-ParentIdColumnEmpty' => [
                    'csvValidatorClasses' => ['CsvParentValidator' => CsvParentValidator::class],
                    'filename' => '/unix_csv_parent_id_column_empty.csv',
                    'testname' => 'CsvParentValidator',
                    'validatorOptions' => [
                        'verbose' => true,
                    ],
                    CsvValidatorResult::TEST_TITLE => CsvParentValidator::TITLE,
                    CsvValidatorResult::TEST_STATUS => CsvValidatorResult::RESULT_INFO,
                    CsvValidatorResult::TEST_RESULTS => [
                        'Rows with parentId populated: 0',
                    ],
                    CsvValidatorResult::TEST_DETAIL => [
                    ],
                ],
            ],

            [
                'CsvParentValidator-ParentIdNoMatches' => [
                    'csvValidatorClasses' => ['CsvParentValidator' => CsvParentValidator::class],
                    'filename' => '/unix_csv_without_utf8_bom.csv',
                    'testname' => 'CsvParentValidator',
                    'validatorOptions' => [
                        'verbose' => true,
                    ],
                    CsvValidatorResult::TEST_TITLE => CsvParentValidator::TITLE,
                    CsvValidatorResult::TEST_STATUS => CsvValidatorResult::RESULT_ERROR,
                    CsvValidatorResult::TEST_RESULTS => [
                        'Rows with parentId populated: 3',
                        "'source' option not specified. Unable to check parentId values against AtoM's database.",
                        'Number of rows for which parents could not be found (will import as top level records): 3',
                    ],
                    CsvValidatorResult::TEST_DETAIL => [
                        'B10101,DJ001,ID1,Some Photographs,,Extent and medium 1,,',
                        'D20202,DJ002,,Voûte, étagère 0074,,,,',
                        ',DJ003,ID4,Title Four,,,,en',
                    ],
                ],
            ],

            [
                'CsvParentValidator-ParentIdMatchesInFile' => [
                    'csvValidatorClasses' => ['CsvParentValidator' => CsvParentValidator::class],
                    'filename' => '/unix_csv_parent_id_matches.csv',
                    'testname' => 'CsvParentValidator',
                    'validatorOptions' => [
                        'verbose' => true,
                    ],
                    CsvValidatorResult::TEST_TITLE => CsvParentValidator::TITLE,
                    CsvValidatorResult::TEST_STATUS => CsvValidatorResult::RESULT_INFO,
                    CsvValidatorResult::TEST_RESULTS => [
                        'Rows with parentId populated: 1',
                    ],
                    CsvValidatorResult::TEST_DETAIL => [
                    ],
                ],
            ],

            [
                'CsvParentValidator-ParentIdMatchesInFileWithSourceOption' => [
                    'csvValidatorClasses' => ['CsvParentValidator' => CsvParentValidator::class],
                    'filename' => '/unix_csv_parent_id_matches.csv',
                    'testname' => 'CsvParentValidator',
                    'validatorOptions' => [
                        'source' => 'testsourcefile.csv',
                        'verbose' => true,
                    ],
                    CsvValidatorResult::TEST_TITLE => CsvParentValidator::TITLE,
                    CsvValidatorResult::TEST_STATUS => CsvValidatorResult::RESULT_INFO,
                    CsvValidatorResult::TEST_RESULTS => [
                        'Rows with parentId populated: 1',
                    ],
                    CsvValidatorResult::TEST_DETAIL => [
                    ],
                ],
            ],

            [
                'CsvParentValidator-ParentIdMatchesInKeymap' => [
                    'csvValidatorClasses' => ['CsvParentValidator' => CsvParentValidator::class],
                    'filename' => '/unix_csv_parent_id_matches_in_keymap.csv',
                    'testname' => 'CsvParentValidator',
                    'validatorOptions' => [
                        'verbose' => true,
                    ],
                    CsvValidatorResult::TEST_TITLE => CsvParentValidator::TITLE,
                    CsvValidatorResult::TEST_STATUS => CsvValidatorResult::RESULT_ERROR,
                    CsvValidatorResult::TEST_RESULTS => [
                        'Rows with parentId populated: 1',
                        "'source' option not specified. Unable to check parentId values against AtoM's database.",
                        'Number of rows for which parents could not be found (will import as top level records): 1',
                    ],
                    CsvValidatorResult::TEST_DETAIL => [
                        'D20202,A10101,,Voûte, étagère 0074,,,,',
                    ],
                ],
            ],

            [
                'CsvParentValidator-ParentIdMatchesInKeymapWithSourceOption' => [
                    'csvValidatorClasses' => ['CsvParentValidator' => CsvParentValidator::class],
                    'filename' => '/unix_csv_parent_id_matches_in_keymap.csv',
                    'testname' => 'CsvParentValidator',
                    'validatorOptions' => [
                        'source' => 'testsourcefile.csv',
                        'verbose' => true,
                    ],
                    CsvValidatorResult::TEST_TITLE => CsvParentValidator::TITLE,
                    CsvValidatorResult::TEST_STATUS => CsvValidatorResult::RESULT_INFO,
                    CsvValidatorResult::TEST_RESULTS => [
                        'Rows with parentId populated: 1',
                    ],
                    CsvValidatorResult::TEST_DETAIL => [
                    ],
                ],
            ],

            [
                'CsvParentValidator-QubitParentSlug' => [
                    'csvValidatorClasses' => ['CsvParentValidator' => CsvParentValidator::class],
                    'filename' => '/unix_csv_qubit_parent_slug.csv',
                    'testname' => 'CsvParentValidator',
                    'validatorOptions' => [
                        'verbose' => true,
                    ],
                    CsvValidatorResult::TEST_TITLE => CsvParentValidator::TITLE,
                    CsvValidatorResult::TEST_STATUS => CsvValidatorResult::RESULT_ERROR,
                    CsvValidatorResult::TEST_RESULTS => [
                        'Rows with qubitParentSlug populated: 2',
                        'Number of rows for which parents could not be found (will import as top level records): 1',
                    ],
                    CsvValidatorResult::TEST_DETAIL => [
                        'X7,missing-slug,TY99,Some stuff,,,,en',
                    ],
                ],
            ],

            [
                'CsvParentValidator-QubitParentIdParentSlugEmpty' => [
                    'csvValidatorClasses' => ['CsvParentValidator' => CsvParentValidator::class],
                    'filename' => '/unix_csv_parent_id_and_qubit_parent_slug.csv',
                    'testname' => 'CsvParentValidator',
                    'validatorOptions' => [
                        'verbose' => true,
                    ],
                    CsvValidatorResult::TEST_TITLE => CsvParentValidator::TITLE,
                    CsvValidatorResult::TEST_STATUS => CsvValidatorResult::RESULT_WARN,
                    CsvValidatorResult::TEST_RESULTS => [
                        'Rows with parentId populated: 1',
                        'Rows with qubitParentSlug populated: 2',
                        'Rows with both \'parentId\' and \'qubitParentSlug\' populated: 1',
                        'Column \'qubitParentSlug\' will override \'parentId\' if both are populated.',
                    ],
                    CsvValidatorResult::TEST_DETAIL => [
                    ],
                ],
            ],
        ];
    }

    // Generic Validation
    protected function runValidator($csvValidator, $filenames, $tests, $verbose = true)
    {
        $csvValidator->setCsvTests($tests);
        $csvValidator->setFilenames(explode(',', $filenames));
        $csvValidator->setVerbose($verbose);
        $csvValidator->setOrmClasses($this->ormClasses);

        return $csvValidator->validate();
    }
}