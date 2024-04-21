<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Log;

class NewsController extends Controller
{
    private function fetchArticlesFromAPI()
    {
        $newskey = env('NEWS_API_KEY');
        $response = Http::get('https://newsapi.org/v2/top-headlines?country=us&category=business&apiKey=' . $newskey);
        $news = $response->json();
        return $news['articles'];
    }
    public function index(Request $request)
    {
        try {
            $newsdata = $this->fetchArticlesFromAPI();
            $perPage = 10;
            $currentPage = Paginator::resolveCurrentPage('page');
            $pagedData = array_slice($newsdata, ($currentPage - 1) * $perPage, $perPage);
            $newsPaginated = new LengthAwarePaginator($pagedData, count($newsdata), $perPage);

            return view('/news', compact('newsPaginated'));
        } catch (\Exception $e) {
            Log::error('Exception occurred while retrieving articles: ' . $e->getMessage());

            return view('error', ['message' => 'An unexpected error occurred. Please try again later.']);
        }
    }

    public function search(Request $request)
    {
        try {
            $newsdata = $this->fetchArticlesFromAPI();

            $searchKeyword = $request->input('keyword');
            if ($searchKeyword) {
                $newsdata = array_filter($newsdata, function ($article) use ($searchKeyword) {
                    return stripos($article['title'], $searchKeyword) !== false ||
                        stripos($article['content'], $searchKeyword) !== false ||
                        stripos($article['publishedAt'], $searchKeyword) !== false ||
                        stripos($article['author'], $searchKeyword) !== false;
                });
            }

            return response()->json($newsdata);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch search results'], 500);
        }
    }

    public function filterByPublishedDate(Request $request)
    {
        try {
            $newsdata = $this->fetchArticlesFromAPI();

            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');

            if ($startDate && $endDate) {
                $newsdata = array_filter($newsdata, function ($article) use ($startDate, $endDate) {
                    $publishedAt = strtotime($article['publishedAt']);
                    return $publishedAt >= strtotime($startDate) && $publishedAt <= strtotime($endDate);
                });
            }

            return response()->json($newsdata);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch filtered results'], 500);
        }
    }
}
