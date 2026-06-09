<?php

namespace App\Http\Controllers;

use App\Http\Requests\Menu\StoreMenuRequest;
use App\Http\Requests\Menu\UpdateMenuRequest;
use App\Models\Menu;
use App\Services\MenuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class MenuController extends Controller
{
    public function __construct(private readonly MenuService $menuService) {}

    public function index(): View
    {
        return view('pages.menus.index');
    }

    public function data(): JsonResponse
    {
        return DataTables::eloquent($this->menuService->getDataTableQuery())
            ->addColumn(
                'menu',
                fn (Menu $menu) => view('pages.menus.columns.menu', compact('menu'))->render(),
            )
            ->addColumn(
                'destination',
                fn (Menu $menu) => view('pages.menus.columns.destination', compact('menu'))->render(),
            )
            ->addColumn(
                'status',
                fn (Menu $menu) => view('pages.menus.columns.status', compact('menu'))->render(),
            )
            ->addColumn(
                'action',
                fn (Menu $menu) => view('pages.menus.columns.action', compact('menu'))->render(),
            )
            ->filterColumn('name', function ($query, string $keyword): void {
                $query->where('name', 'like', "%{$keyword}%");
            })
            ->filterColumn('destination', function ($query, string $keyword): void {
                $query->where(function ($query) use ($keyword): void {
                    $query
                        ->where('route_name', 'like', "%{$keyword}%")
                        ->orWhere('url', 'like', "%{$keyword}%");
                });
            })
            ->only([
                'menu',
                'section',
                'destination',
                'sort_order',
                'status',
                'action',
            ])
            ->rawColumns(['menu', 'destination', 'status', 'action'])
            ->toJson();
    }

    public function create(): View
    {
        return view('pages.menus.create', [
            'menu' => new Menu([
                'icon' => 'circle',
                'target' => '_self',
                'sort_order' => 0,
                'is_active' => true,
            ]),
            'icons' => Menu::ICONS,
        ]);
    }

    public function store(StoreMenuRequest $request): RedirectResponse
    {
        $menu = $this->menuService->create($request->validated());

        return redirect()
            ->route('menus.index')
            ->with('success', "Menu \"{$menu->name}\" created successfully.");
    }

    public function edit(Menu $menu): View
    {
        return view('pages.menus.edit', [
            'menu' => $menu,
            'icons' => Menu::ICONS,
        ]);
    }

    public function update(UpdateMenuRequest $request, Menu $menu): RedirectResponse
    {
        $menu = $this->menuService->update($menu, $request->validated());

        return redirect()
            ->route('menus.index')
            ->with('success', "Menu \"{$menu->name}\" updated successfully.");
    }

    public function destroy(Request $request, Menu $menu): RedirectResponse|JsonResponse
    {
        $name = $menu->name;
        $this->menuService->delete($menu);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Menu \"{$name}\" deleted successfully.",
            ]);
        }

        return redirect()
            ->route('menus.index')
            ->with('info', "Menu \"{$name}\" deleted.");
    }
}
