<?php

namespace App\Http\Controllers\Nutrition;

use App\Enums\MenuStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMenuItemRequest;
use App\Http\Requests\StoreMenuRequest;
use App\Http\Requests\UpdateMenuDraftRequest;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Services\MenuManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MenuManagementController extends Controller
{
    /**
     * Display a listing of menus in the tenant SPPG.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Menu::class);

        $user = $request->user();
        $sppgId = (int) $user->getEffectiveSppgId();

        $query = Menu::forSppg($sppgId)
            ->with(['items', 'creator', 'approver'])
            ->orderByDesc('updated_at');

        // Filter status
        if ($request->filled('status')) {
            $statusValue = $request->input('status');
            if (in_array($statusValue, ['DRAFT', 'ACTIVE', 'ARCHIVED'], true)) {
                $query->where('status', $statusValue);
            }
        }

        // Search by keyword
        if ($request->filled('q')) {
            $keyword = trim($request->input('q'));
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('menu_code', 'like', "%{$keyword}%");
            });
        }

        $menus = $query->paginate(15)->withQueryString();

        // Calculate summary metrics for the header
        $allTenantMenus = Menu::forSppg($sppgId)->with('items')->get();
        $activeMenusCount = $allTenantMenus->where('status', MenuStatus::ACTIVE)->count();
        $draftMenusCount = $allTenantMenus->where('status', MenuStatus::DRAFT)->count();
        $incompleteDraftsCount = $allTenantMenus
            ->where('status', MenuStatus::DRAFT)
            ->filter(fn (Menu $m) => ! $m->isComplete())
            ->count();

        return view('nutrition.menus.index', [
            'user' => $user,
            'menus' => $menus,
            'activeMenusCount' => $activeMenusCount,
            'draftMenusCount' => $draftMenusCount,
            'incompleteDraftsCount' => $incompleteDraftsCount,
            'currentFilter' => $request->input('status', 'all'),
            'searchQuery' => $request->input('q', ''),
        ]);
    }

    /**
     * Show form for creating a new menu draft.
     */
    public function create(): View
    {
        Gate::authorize('create', Menu::class);

        return view('nutrition.menus.create');
    }

    /**
     * Store a newly created menu draft.
     */
    public function store(StoreMenuRequest $request, MenuManagementService $service): RedirectResponse
    {
        $user = $request->user();
        $menu = $service->createDraft($request->validated(), (int) $user->sppg_id, $user);

        return redirect()
            ->route('nutrition.menus.show', $menu)
            ->with('status', "Draft menu \"{$menu->name}\" (v{$menu->version}) berhasil dibuat. Silakan tambahkan komponen bahan dan kandungan gizi.");
    }

    /**
     * Display the specified menu dossier.
     */
    public function show(Menu $menu): View
    {
        Gate::authorize('view', $menu);

        $menu->load(['items', 'creator', 'approver', 'sppg']);
        $versionHistory = $menu->getVersionHistory();
        $violations = $menu->completenessViolations();

        return view('nutrition.menus.show', [
            'menu' => $menu,
            'items' => $menu->items,
            'versionHistory' => $versionHistory,
            'isComplete' => $menu->isComplete(),
            'violations' => $violations,
            'totalWeight' => $menu->totalServingWeight(),
            'totalEnergy' => $menu->totalEnergy(),
            'totalProtein' => $menu->totalProtein(),
            'totalCarbs' => $menu->totalCarbohydrates(),
            'totalFat' => $menu->totalFat(),
            'totalFiber' => $menu->totalFiber(),
        ]);
    }

    /**
     * Show form for editing a draft menu.
     */
    public function edit(Menu $menu): View|RedirectResponse
    {
        Gate::authorize('update', $menu);

        if (! $menu->isDraft()) {
            return redirect()
                ->route('nutrition.menus.show', $menu)
                ->with('error', 'Menu berstatus Aktif atau Arsip bersifat immutable dan tidak dapat disunting. Gunakan opsi "Buat Versi Baru" untuk merevisi resep.');
        }

        $menu->load('items');

        return view('nutrition.menus.edit', [
            'menu' => $menu,
            'items' => $menu->items,
        ]);
    }

    /**
     * Update the draft menu metadata.
     */
    public function update(UpdateMenuDraftRequest $request, Menu $menu, MenuManagementService $service): RedirectResponse
    {
        Gate::authorize('update', $menu);

        $service->updateDraft($menu, $request->validated(), $request->user());

        return redirect()
            ->route('nutrition.menus.show', $menu)
            ->with('status', 'Informasi draft menu berhasil diperbarui.');
    }

    /**
     * Add a food item component to a draft menu.
     */
    public function addItem(StoreMenuItemRequest $request, Menu $menu, MenuManagementService $service): RedirectResponse
    {
        Gate::authorize('update', $menu);

        $service->addMenuItem($menu, $request->validated(), $request->user());

        return redirect()
            ->route('nutrition.menus.show', $menu)
            ->with('status', 'Komponen bahan makanan berhasil ditambahkan ke dalam menu.');
    }

    /**
     * Delete a food item component from a draft menu.
     */
    public function deleteItem(Request $request, Menu $menu, MenuItem $item, MenuManagementService $service): RedirectResponse
    {
        Gate::authorize('update', $menu);

        if ((int) $item->menu_id !== (int) $menu->id) {
            abort(404, 'Komponen bahan tidak ditemukan pada menu ini.');
        }

        $service->deleteMenuItem($item, $request->user());

        return redirect()
            ->route('nutrition.menus.show', $menu)
            ->with('status', 'Komponen bahan makanan berhasil dihapus dari draft menu.');
    }

    /**
     * Validate completeness and activate the draft menu.
     */
    public function activate(Request $request, Menu $menu, MenuManagementService $service): RedirectResponse
    {
        Gate::authorize('activate', $menu);

        try {
            $activatedMenu = $service->activateMenu($menu, $request->user());

            return redirect()
                ->route('nutrition.menus.show', $activatedMenu)
                ->with('status', "Versi menu \"{$activatedMenu->name}\" v{$activatedMenu->version} berhasil disahkan dan berstatus Aktif.");
        } catch (ValidationException $e) {
            $messages = $e->errors();
            $firstMessage = collect($messages)->flatten()->first() ?? 'Validasi kelengkapan menu tidak terpenuhi.';

            return redirect()
                ->route('nutrition.menus.show', $menu)
                ->with('error', $firstMessage);
        }
    }

    /**
     * Branch a new Draft version from an existing menu.
     */
    public function newVersion(Request $request, Menu $menu, MenuManagementService $service): RedirectResponse
    {
        Gate::authorize('createNewVersion', $menu);

        $newMenu = $service->createNewVersion($menu, $request->user());

        return redirect()
            ->route('nutrition.menus.show', $newMenu)
            ->with('status', "Cabang versi baru (v{$newMenu->version}) berhasil dibuat sebagai Draft. Versi v{$menu->version} sebelumnya tetap aman dan tidak berubah.");
    }
}
