<?php

namespace App\Articles;

use App\Article;
use Elasticsearch\Client;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use App\Articles\ArticlesRepository;
use Illuminate\Database\Eloquent\Collection;


class ElasticsearchRepository implements ArticlesRepository
{
     /** @var \Elasticsearch\Client */
    private $elasticsearch;

    public function __construct(Client $elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;
    }

    public function search(string $query = ''): Collection
    {
        $items = $this->searchOnElasticsearch($query);
        
        return $this->buildCollection($items);
    }

    public function searchOnElasticsearch(string $query = ''): array
    {
        $model = new Article;
        
        $items = $this->elasticsearch->search([
            'index' => $model->getSearchIndex(),
            'type' => $model->getSearchType(),
            'body' => [
                'sort' => [
                    '_score'
                ],
                'query' => [
                    'bool' => [
                        'should' => [
                            ['match' => [
                                'title' => [
                                    'query'     => $query,
                                    'fuzziness' => 'AUTO'
                                ]
                            ]],
                            ['match' => [
                                'time' => [
                                    'query'     => $query,
                                    'fuzziness' => '0'
                                ]
                            ]],
                            ['match' => [
                                'claim' => [
                                    'query'     => $query,
                                    'fuzziness' => '0'
                                ]
                            ]]
                        ]
                    ],
                ],
            ]
        ]);

        return $items;
    }

    private function buildCollection(array $items): Collection
    {
        $ids = Arr::pluck($items['hits']['hits'], '_id');

        return Article::findMany($ids)
            ->sortBy(function ($article) use ($ids) {
                return array_search($article->getKey(), $ids);
            });
    }
}
