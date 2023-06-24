<?php

namespace App\Repositories\Jobseeker\Frontend;

use Elasticsearch\Client;
use App\ElasticSearchService\ElasticSearchService;

class JobQueryRepository implements JobQueryInterface
{
    
    /** @var \Elasticsearch\Client */
    protected $elasticsearch;

    function __construct(Client $elasticsearch,ElasticSearchService $elasticSearchServiceObj) {
        $this->elasticsearch = $elasticsearch;
        $this->elasticSearchServiceObj = $elasticSearchServiceObj;
    }

private function searchOnElasticsearch(string $query = '', $data): array
{ 
        $model = new Job;
        //This is for rerecommendedJobs
        if ($query == 'null') {
            $query = null;
        }
        //This is for rerecommendedJobs

        if (!empty($data['is_condition']) && $data['is_condition'] == 'should' & empty($query)) {
            $condition = 'should';
        } else {
            $condition = 'must';
        }

        if (!empty($data['search_field'])) {
            $fields = $data['search_field'];
        } else {
            $fields = ['job_title^10', 'job_title_ar^10','job_desc^4', 'job_desc_ar^4', 'skill_list^3', 'skill_list_ar^3', 'functional_area_list^3', 'functional_area_list_ar^3', 'industry_list^2', 'industry_list_ar^2'];
        }
        if (Config::get('global.language.locale') == 'en') {
            $term = [
                [
                    "term" => [
                        "is_deleted" => 0
                    ]
                ],
                [
                    "term" => [
                        "ar_flag" => Config::get('global.language.locale') == 'en' ? 0 : 1,
                    ]
                ],
                [
                    "term" => [
                        "status" => 1,
                    ]
                ],
            ];
        } else {
            $term = [
                [
                    "term" => [
                        "is_deleted" => 0
                    ]
                ],
                [
                    "term" => [
                        "status" => 1,
                    ]
                ],
            ];
        }
        
        $items = $this->elasticsearch->search([
                'index' => $model->getSearchIndex(),
                'type' => $model->getSearchType(),
                'body' => [
                    "track_total_hits" => true,
                    'query' => [
                        "bool" => [
                            $condition => [
                                [
                                    'multi_match' => [
                                        'fields' => $fields,
                                        'query' => $query,
                                    ],
                                ],
                                [
                                    "terms" => [
                                        $data['filterKey'] => $data['filterData']
                                    ]
                                ],
                            ],
                            'should' => [
                                'multi_match' => [
                                    'fields' => ['company_post^10'],
                                    "query" => 1,
                                    "boost" => 5
                                ],
                            ],
                            "filter" => $term
                        ],
                    ],
                    "sort" => [
                        [   
                            $data['orderByKey'] => [   
                                "order" => $data['orderByValue']
                            ] 
                        ],
                        [   
                            '_id' => [   
                                "order" => $data['orderByValue']
                            ] 
                        ],
                    ],
                    "from" => !empty($data['skip']) ? $data['skip'] : 0,
                    "size" => !empty($data['take']) ? $data['take'] : Config::get('global.PER_PAGE_RECORD')
                ],
            ]);
        return $items;
    }
}
