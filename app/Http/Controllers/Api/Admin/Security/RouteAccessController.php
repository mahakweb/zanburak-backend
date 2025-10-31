<?php

namespace App\Http\Controllers\Api\Admin\Security;

use App\Http\Controllers\Controller;
use App\Models\PermissionRoute;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route as RouteFacade;

class RouteAccessController extends Controller
{
	public function index(Request $request)
	{
		$search = $request->input('q', $request->input('search'));
		$perPage = (int) ($request->input('per_page', 20));
		$perPage = $perPage > 0 ? min($perPage, 100) : 20;
		$page = (int) ($request->input('page', 1));
		$page = $page > 0 ? $page : 1;

		$routes = collect(RouteFacade::getRoutes())
			->filter(function ($route) {
				$uri = method_exists($route, 'uri') ? $route->uri() : $route->uri;
				return is_string($uri) && (substr($uri, 0, 4) === 'api/');
			})
			->map(function ($route) {
				$name = $route->getName();
				$uri = $route->uri();
				$methods = array_values(array_unique(array_filter($route->methods(), function ($m) {
					return $m !== 'HEAD';
				})));

				return [
					'name' => $name,
					'uri' => $uri,
					'methods' => $methods,
					'is_named' => $name !== null,
				];
			});

		if ($search) {
			$searchLower = mb_strtolower($search);
			$routes = $routes->filter(function ($r) use ($searchLower) {
				$name = $r['name'] ? mb_strtolower($r['name']) : '';
				$uri = mb_strtolower($r['uri']);
				return (str_contains($name, $searchLower) || str_contains($uri, $searchLower));
			});
		}

		// Sort by uri then name for consistent results
		$routes = $routes->sortBy([['uri', 'asc'], ['name', 'asc']])->values();

		$total = $routes->count();
		$offset = ($page - 1) * $perPage;
		$currentItems = $routes->slice($offset, $perPage)->values();

		// Map permissions for current page by route name
		$routeNames = $currentItems->pluck('name')->filter()->values();
		$permissionsByRoute = [];
		if ($routeNames->isNotEmpty()) {
			PermissionRoute::with('permission')
				->whereIn('route_name', $routeNames)
				->get()
				->groupBy('route_name')
				->each(function ($group, $routeName) use (&$permissionsByRoute) {
					$permissionsByRoute[$routeName] = $group->map(function ($pr) {
						return [
							'id' => $pr->permission?->id,
							'name' => $pr->permission?->name,
							'label' => $pr->permission?->label,
						];
					})->values()->all();
				});
		}

		$data = $currentItems->map(function ($r) use ($permissionsByRoute) {
			$r['permissions'] = $r['name'] && isset($permissionsByRoute[$r['name']])
				? $permissionsByRoute[$r['name']]
				: [];
			return $r;
		});

		$paginator = new LengthAwarePaginator(
			$data,
			$total,
			$perPage,
			$page,
			['path' => request()->url(), 'query' => request()->query()]
		);

		return response()->json([
			'message' => 'Success',
			'routes' => $paginator->items(),
			'pagination' => [
				'current_page' => $paginator->currentPage(),
				'last_page' => $paginator->lastPage(),
				'per_page' => $paginator->perPage(),
				'total' => $paginator->total(),
				'from' => $paginator->firstItem(),
				'to' => $paginator->lastItem(),
			],
		], 200);
	}

	public function addPermissions(Request $request)
	{
		$validated = $request->validate([
			'route_name' => 'required|string',
			'permissions' => 'required|array|min:1',
			'permissions.*' => 'required|integer|exists:permissions,id',
		]);

		$routeName = $validated['route_name'];
		$permissionIds = collect($validated['permissions'])->unique()->values();

		$created = [];
		foreach ($permissionIds as $pid) {
			PermissionRoute::firstOrCreate([
				'permission_id' => $pid,
				'route_name' => $routeName,
			]);
			$created[] = $pid;
		}

		$permissions = \App\Models\Permission::whereIn('id', $created)->get(['id','name','label']);

		return response()->json([
			'message' => 'Permissions added to route',
			'route_name' => $routeName,
			'permissions' => $permissions,
		], 201);
	}

	public function removePermission(Request $request)
	{
		$validated = $request->validate([
			'route_name' => 'required|string',
			'permission' => 'required|integer|exists:permissions,id',
		]);

		PermissionRoute::where('route_name', $validated['route_name'])
			->where('permission_id', $validated['permission'])
			->delete();

		return response()->json([
			'message' => 'Permission removed from route',
			'route_name' => $validated['route_name'],
			'permission_id' => $validated['permission'],
		], 200);
	}
}


