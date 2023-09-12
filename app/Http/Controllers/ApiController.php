<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{
    private $apiKey;

    public function __construct() {
        $this->apiKey = env("TMDB_API_KEY");
    }

    public function getTop(Request $request) {
        $data = $request->all();
        $accept = $request->header("accept");

        $country = $data["country"];
        $apiKey = $this->apiKey;

        try {
            $request = Http::withHeaders([
                "Authorization" => "Bearer $apiKey",
                "accept" => $accept
            ])->get("https://api.themoviedb.org/3/movie/top_rated?language=en-US&page=1&region=$country");

            $response = $request->body();
            $response = json_decode($response, true);

            return response()->json(["status" => true, "message" => $response]);
        } catch(\Exception $e) {
            return response()->json(["status" => false, "message" => $e->getMessage()]);
        }
    }

    public function rating(Request $request) {
        $title = $request->title;
        $point = $request->point;

        try {
            $movie = DB::table("movies")->where("title", $title)->first();
            if($movie) {
                $favourites = DB::table("movies")->where("title", $title)->value("favourites");
                $new_count = max($favourites + $point, 0);
                DB::table("movies")->where("title", $title)->update(["favourites" => $new_count]);

                return response()->json(["status" => true]);
            } else {
                DB::table("movies")->insert([
                    "title" => $title,
                    "favourites" => $point == -1 ? 0 : 1 
                ]);

                return response()->json(["status" => true]);
            }
        } catch(\Exception $e) {
            return response()->json(["status" => false, "error" => $e->getMessage()]);
        }
    }


    public function getRating($title) {
        try {
            $movie = DB::table("movies")->where("title", $title)->first();
            if($movie) {
                $favourites = DB::table("movies")->where("title", $title)->value("favourites");
                return response()->json(["favourites" => $favourites]);
            } else {
                return response()->json(["favourites" => 0]);
            }
        } catch(\Exception $e) {
            return response()->json(["favourites" => 0]);
        }
    }
}
