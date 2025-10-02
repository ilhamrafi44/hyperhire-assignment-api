<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Person, Like, Dislike};
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * ====== Component Schemas ======
 *
 * @OA\Schema(
 *   schema="Picture",
 *   type="object",
 *   @OA\Property(property="url", type="string", format="uri")
 * )
 *
 * @OA\Schema(
 *   schema="Location",
 *   type="object",
 *   @OA\Property(property="lat", type="number", format="float", nullable=true),
 *   @OA\Property(property="lng", type="number", format="float", nullable=true),
 *   @OA\Property(property="city", type="string", nullable=true),
 *   @OA\Property(property="distanceKm", type="number", format="float", nullable=true)
 * )
 *
 * @OA\Schema(
 *   schema="Person",
 *   type="object",
 *   @OA\Property(property="id", type="string"),
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="age", type="integer"),
 *   @OA\Property(property="location", ref="#/components/schemas/Location"),
 *   @OA\Property(
 *     property="pictures",
 *     type="array",
 *     @OA\Items(ref="#/components/schemas/Picture")
 *   )
 * )
 *
 * @OA\Schema(
 *   schema="PagePerson",
 *   type="object",
 *   @OA\Property(
 *     property="items",
 *     type="array",
 *     @OA\Items(ref="#/components/schemas/Person")
 *   ),
 *   @OA\Property(property="page", type="integer", description="0-based page index"),
 *   @OA\Property(property="size", type="integer"),
 *   @OA\Property(property="total", type="integer")
 * )
 */
class PeopleController extends Controller
{
    /**
     * GET /people?page=0&size=20&lat=&lng=
     *
     * @OA\Get(
     *   path="/people",
     *   tags={"people"},
     *   summary="List of recommended people (paginated)",
     *   description="Returns paginated people list. Client uses 0-based `page`.",
     *   @OA\Parameter(name="page", in="query", @OA\Schema(type="integer"), description="0-based page index"),
     *   @OA\Parameter(name="size", in="query", @OA\Schema(type="integer"), description="Items per page"),
     *   @OA\Parameter(name="lat", in="query", @OA\Schema(type="number", format="float")),
     *   @OA\Parameter(name="lng", in="query", @OA\Schema(type="number", format="float")),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(ref="#/components/schemas/PagePerson")
     *   )
     * )
     */
    public function index(Request $req)
    {
        $size = (int)($req->integer('size', 20));
        $page = (int)($req->integer('page', 0));     // client 0-based
        $page = max(1, $page + 1);                   // laravel 1-based

        $query = Person::with(['pictures'])->orderByDesc('id');

        // optional: distance jika lat/lng diberikan
        $lat = $req->float('lat');
        $lng = $req->float('lng');
        if ($lat && $lng) {
            $haversine = "(6371 * acos(cos(radians($lat)) * cos(radians(lat)) * cos(radians(lng) - radians($lng)) + sin(radians($lat)) * sin(radians(lat))))";
            $query->select('*')->selectRaw("$haversine AS distance_km")->orderBy('distance_km');
        }

        $paginator = $query->paginate($size, ['*'], 'page', $page);

        $items = $paginator->getCollection()->map(function ($p) {
            return [
                'id'   => (string)$p->id,
                'name' => $p->name,
                'age'  => $p->age,
                'location' => [
                    'lat' => $p->lat,
                    'lng' => $p->lng,
                    'city' => $p->city,
                    'distanceKm' => isset($p->distance_km) ? round($p->distance_km, 1) : null,
                ],
                'pictures' => $p->pictures->map(fn ($pic) => ['url' => $pic->url])->values(),
            ];
        });

        return response()->json([
            'items' => $items,
            'page'  => $paginator->currentPage() - 1,   // back to 0-based
            'size'  => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }

    /**
     * Like a person
     *
     * @OA\Post(
     *   path="/people/{id}/like",
     *   tags={"people"},
     *   summary="Like a person",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(
     *     name="X-Device-Id",
     *     in="header",
     *     required=true,
     *     description="Anonymous device id",
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="liked", type="boolean"),
     *       @OA\Property(property="person_id", type="integer")
     *     )
     *   ),
     *   @OA\Response(response=422, description="X-Device-Id required")
     * )
     */
    public function like($id, Request $req)
    {
        $deviceId = $req->header('X-Device-Id') ?? $req->string('device_id');
        if (!$deviceId) {
            return response()->json(['message' => 'X-Device-Id required'], 422);
        }

        $person = Person::findOrFail($id);
        Like::firstOrCreate(['person_id' => $person->id, 'device_id' => $deviceId]);

        return response()->json(['liked' => true, 'person_id' => $person->id]);
    }

    /**
     * Dislike a person
     *
     * @OA\Post(
     *   path="/people/{id}/dislike",
     *   tags={"people"},
     *   summary="Dislike a person",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(
     *     name="X-Device-Id",
     *     in="header",
     *     required=true,
     *     description="Anonymous device id",
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="disliked", type="boolean"),
     *       @OA\Property(property="person_id", type="integer")
     *     )
     *   ),
     *   @OA\Response(response=422, description="X-Device-Id required")
     * )
     */
    public function dislike($id, Request $req)
    {
        $deviceId = $req->header('X-Device-Id') ?? $req->string('device_id');
        if (!$deviceId) {
            return response()->json(['message' => 'X-Device-Id required'], 422);
        }

        $person = Person::findOrFail($id);
        Dislike::firstOrCreate(['person_id' => $person->id, 'device_id' => $deviceId]);

        return response()->json(['disliked' => true, 'person_id' => $person->id]);
    }

    /**
     * Liked opponents (by device)
     *
     * @OA\Get(
     *   path="/people/liked",
     *   tags={"people"},
     *   summary="Get liked people for this device",
     *   @OA\Parameter(
     *     name="X-Device-Id",
     *     in="header",
     *     required=true,
     *     description="Anonymous device id",
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(
     *         property="items",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Person")
     *       )
     *     )
     *   ),
     *   @OA\Response(response=422, description="X-Device-Id required")
     * )
     */
    public function liked(Request $req)
    {
        $deviceId = $req->header('X-Device-Id') ?? $req->string('device_id');
        if (!$deviceId) {
            return response()->json(['message' => 'X-Device-Id required'], 422);
        }

        $ids = Like::where('device_id', $deviceId)->pluck('person_id');

        $people = Person::with('pictures')
            ->whereIn('id', $ids)
            ->orderByDesc('id')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => (string)$p->id,
                    'name' => $p->name,
                    'age' => $p->age,
                    'location' => ['lat' => $p->lat, 'lng' => $p->lng, 'city' => $p->city],
                    'pictures' => $p->pictures->map(fn ($pic) => ['url' => $pic->url])->values(),
                ];
            });

        return response()->json(['items' => $people]);
    }
}
