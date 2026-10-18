<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\AcademicCatalogService;
use App\Services\DepartmentScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AcademicCatalogController extends Controller
{
    public function index(Request $request, string $entity, AcademicCatalogService $catalog, DepartmentScope $scope): View
    {
        $definition = $catalog->definition($entity);
        $records = $catalog->query($entity, $request->user(), $scope)
            ->when($request->filled('search'), function ($query) use ($request, $definition) {
                $search = '%'.$request->string('search').'%';
                $query->where(function ($filter) use ($definition, $search) {
                    $filter->where('name', 'like', $search);

                    if (in_array('code', $definition['fields'], true)) {
                        $filter->orWhere('code', 'like', $search);
                    }
                });
            })
            ->paginate(15)
            ->withQueryString();

        $canManage = $this->canManage($request, $entity);

        return view('admin.catalog.index', compact('definition', 'entity', 'records', 'canManage'));
    }

    public function create(Request $request, string $entity, AcademicCatalogService $catalog): View
    {
        return $this->form($entity, $catalog, null, $request->user());
    }

    public function store(Request $request, string $entity, AcademicCatalogService $catalog, DepartmentScope $scope): RedirectResponse
    {
        $definition = $catalog->definition($entity);
        $data = $catalog->validate($entity, $request->all(), null, $request->user(), $scope);
        $record = $definition['model']::create($data);
        $this->log($request, 'created', $record, null, $data);

        return redirect()->route("admin.academic.{$entity}.index")
            ->with('success', $definition['singular'].' ajouté(e).');
    }

    public function show(Request $request, int $record, string $entity, AcademicCatalogService $catalog, DepartmentScope $scope): View
    {
        $definition = $catalog->definition($entity);
        $item = $catalog->find($entity, $record, $request->user(), $scope);
        $labels = $this->fieldLabels();
        $canManage = $this->canManage($request, $entity);

        return view('admin.catalog.show', compact('definition', 'entity', 'item', 'labels', 'canManage'));
    }

    public function edit(Request $request, int $record, string $entity, AcademicCatalogService $catalog, DepartmentScope $scope): View
    {
        $item = $catalog->find($entity, $record, $request->user(), $scope);

        return $this->form($entity, $catalog, $item, $request->user());
    }

    public function update(Request $request, int $record, string $entity, AcademicCatalogService $catalog, DepartmentScope $scope): RedirectResponse
    {
        $definition = $catalog->definition($entity);
        $item = $catalog->find($entity, $record, $request->user(), $scope);
        $data = $catalog->validate($entity, $request->all(), $item, $request->user(), $scope);
        $oldValues = $item->only(array_keys($data));
        $item->update($data);
        $this->log($request, 'updated', $item, $oldValues, $data);

        return redirect()->route("admin.academic.{$entity}.index")
            ->with('success', $definition['singular'].' modifié(e).');
    }

    public function destroy(Request $request, int $record, string $entity, AcademicCatalogService $catalog, DepartmentScope $scope): RedirectResponse
    {
        $definition = $catalog->definition($entity);
        $item = $catalog->find($entity, $record, $request->user(), $scope);
        $blocker = $catalog->deletionBlocker($entity, $item);

        if ($blocker) {
            throw ValidationException::withMessages(['record' => $blocker]);
        }

        $oldValues = $item->getAttributes();
        $this->log($request, 'deleted', $item, $oldValues, null);
        $item->delete();

        return redirect()->route("admin.academic.{$entity}.index")
            ->with('success', $definition['singular'].' supprimé(e).');
    }

    private function form(string $entity, AcademicCatalogService $catalog, $item = null, ?\App\Models\User $user = null): View
    {
        $definition = $catalog->definition($entity);
        $options = $catalog->options($entity, $user);
        $labels = $this->fieldLabels();

        return view('admin.catalog.form', compact('definition', 'entity', 'item', 'options', 'labels'));
    }

    private function canManage(Request $request, string $entity): bool
    {
        if ($request->user()->hasRole('department_manager')) {
            return in_array($entity, ['formations', 'levels', 'classes', 'groups'], true);
        }

        return ! $request->user()->hasRole(['director_general', 'studies_manager', 'registrar']);
    }

    private function fieldLabels(): array
    {
        return [
            'faculty_id' => 'Faculté',
            'department_id' => 'Département',
            'formation_id' => 'Formation',
            'level_id' => 'Niveau',
            'academic_year_id' => 'Année universitaire',
            'academic_class_id' => 'Classe',
            'name' => 'Nom',
            'code' => 'Code',
            'description' => 'Description',
            'starts_on' => 'Date de début',
            'ends_on' => 'Date de fin',
            'position' => 'Ordre du niveau',
            'capacity' => 'Capacité',
            'is_active' => 'Élément actif',
        ];
    }

    private function log(Request $request, string $action, $item, ?array $oldValues, ?array $newValues): void
    {
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => $item::class,
            'subject_id' => $item->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
        ]);
    }
}
