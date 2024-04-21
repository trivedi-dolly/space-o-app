<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Request;
use App\Http\Controllers\NewsController;
use Illuminate\Support\Facades\Http;
use Illuminate\Pagination\LengthAwarePaginator;
use Log;

class NewsControllerTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function test_example(): void
    {
        $this->assertTrue(true);
    }
    public function test_retrive_articles()
    {

        Http::fake([
            'https://newsapi.org/v2/top-headlines*' => Http::response([
                'status' => 'ok',
                'totalResults' => 1,
                'articles' => [
                    [
                        'title' => 'Test Article',
                        'content' => 'This is a test article',
                        'published_at' => '20-04-2024',
                        'author' => 'Todd A. Price, Gary Estwick'
                    ]
                ]
            ], 200)
        ]);
        $request = Request::create('/', 'GET');

        $controller = new NewsController();

        $response = $controller->index($request);

        $this->assertNotNull($response);

        $this->assertInstanceOf(\Illuminate\View\View::class, $response);

        $this->assertTrue($response->getData()['newsPaginated'] !== null);
    }

    public function test_paginate_articles()
    {
        Http::fake([
            'https://newsapi.org/v2/top-headlines*' => Http::response([
                'status' => 'ok',
                'totalResults' => 20,
                'articles' => [
                    [
                        'title' => 'Test Article',
                        'content' => 'This is a test article',
                        'published_at' => '20-04-2024',
                        'author' => 'Todd A. Price, Gary Estwick'
                    ]
                ]
            ], 200)
        ]);

        $request = Request::create('/', 'GET');

        $controller = new NewsController();

        $response = $controller->index($request);

        $this->assertNotNull($response);

        $this->assertInstanceOf(\Illuminate\View\View::class, $response);

        $this->assertArrayHasKey('newsPaginated', $response->getData());

        $this->assertInstanceOf(LengthAwarePaginator::class, $response->getData()['newsPaginated']);

        $this->assertEquals(10, $response->getData()['newsPaginated']->perPage());

    }

    public function test_failed_api_integration()
    {
        Http::fake([
            'https://newsapi.org/v2/top-headlines*' => Http::response([], 500),
        ]);

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to retrieve articles from News API: 500');

        $request = Request::create('/', 'GET');

        $controller = new NewsController();

        $response = $controller->index($request);

        $this->assertInstanceOf(\Illuminate\View\View::class, $response);

        $this->assertStringContainsString('Failed to retrieve articles. Please try again later.', $response->render());
    }

    public function test_search_articles()
    {
        Http::fake([
            'https://newsapi.org/v2/top-headlines*' => Http::response([
                'status' => 'ok',
                'totalResults' => 3,
                'articles' => [
                    [
                        'title' => 'Test Article 1',
                        'content' => 'This is a test article 1',
                        'publishedAt' => '2024-04-01T12:00:00Z',
                        'author' => 'John Doe',
                    ],
                    [
                        'title' => 'Test Article 2',
                        'content' => 'This is a test article 2',
                        'publishedAt' => '2024-04-02T12:00:00Z',
                        'author' => 'Jane Doe',
                    ],
                    [
                        'title' => 'Article 3',
                        'content' => 'This is a test article 3',
                        'publishedAt' => '2024-04-03T12:00:00Z',
                        'author' => 'John Smith',
                    ],
                ],
            ], 200)
        ]);

        $request = Request::create('/search', 'GET', ['keyword' => 'Test']);

        $controller = new NewsController();

        $response = $controller->search($request);

        $this->assertNotEmpty($response);

        $responseData = json_decode($response->content(), true);

        $expectedResults = [
            [
                'title' => 'Test Article 1',
                'content' => 'This is a test article 1',
                'publishedAt' => '2024-04-01T12:00:00Z',
                'author' => 'John Doe',
            ],
            [
                'title' => 'Test Article 2',
                'content' => 'This is a test article 2',
                'publishedAt' => '2024-04-02T12:00:00Z',
                'author' => 'Jane Doe',
            ]
        ];

        foreach ($expectedResults as $expectedResult) {
            $this->assertContains($expectedResult, $responseData);
        }
    }

    public function test_search_no_articles_found()
    {
        // Mock the HTTP client to return an empty response
        Http::fake([
            'https://newsapi.org/v2/top-headlines*' => Http::response([
                'status' => 'ok',
                'totalResults' => 3,
                'articles' => [
                    [
                        'title' => 'Test Article 1',
                        'content' => 'This is a test article 1',
                        'publishedAt' => '2024-04-01T12:00:00Z',
                        'author' => 'John Doe',
                    ],
                    [
                        'title' => 'Test Article 2',
                        'content' => 'This is a test article 2',
                        'publishedAt' => '2024-04-02T12:00:00Z',
                        'author' => 'Jane Doe',
                    ],
                    [
                        'title' => 'Article 3',
                        'content' => 'This is a test article 3',
                        'publishedAt' => '2024-04-03T12:00:00Z',
                        'author' => 'John Smith',
                    ],
                ],
            ], 200)
        ]);

        $request = Request::create('/search', 'GET', ['keyword' => 'NonExistent']);

        $controller = new NewsController();

        $response = $controller->search($request);

        $this->assertNotEmpty($response);

        $responseData = json_decode($response->content(), true);

        $this->assertIsArray($responseData);

        $this->assertEmpty($responseData);
    }

    public function test_filter_by_published_date()
    {
        Http::fake([
            'https://newsapi.org/v2/top-headlines*' => Http::response([
                'status' => 'ok',
                'totalResults' => 3,
                'articles' => [
                    ['title' => 'Article 1', 'publishedAt' => '2022-04-20T08:00:00Z'],
                    ['title' => 'Article 2', 'publishedAt' => '2022-04-21T08:00:00Z'],
                    ['title' => 'Article 3', 'publishedAt' => '2022-04-20T08:00:00Z'],
                ]
            ], 200)
        ]);

        $request = Request::create('/filter', 'GET', [
            'startDate' => '2022-04-21',
            'endDate' => '2022-04-22'
        ]);

        $controller = new NewsController();

        $response = $controller->filterByPublishedDate($request);

        $this->assertNotEmpty($response);

        $responseData = json_decode($response->content(), true);

        $this->assertIsArray($responseData);
        $this->assertCount(1, $responseData);
        $this->assertEquals('Article 2', $responseData[1]['title']);
    }
}
