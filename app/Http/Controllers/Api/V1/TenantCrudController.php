<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

abstract class TenantCrudController extends Controller
{
    /** @var class-string<Model> */
    protected string $modelClass;

    /** @var list<string> */
    protected array $searchable = [];

    /** @var list<string> */
    protected array $filterable = [];

    /** @return array<string, mixed> */
    abstract protected function rules(?Model $record = null): array;

    public function __construct(protected readonly AuditService $audit) {}

    protected function tenantExists(string $table): Exists
    {
        $organizationId = app(TenantContext::class)->organizationId();

        return Rule::exists($table, 'id')->where(fn ($query) => $query->where('organization_id', $organizationId));
    }

    public function index(Request $request): JsonResponse
    {
        $filterRules = array_fill_keys($this->filterable, ['nullable', 'string', 'max:255']);
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            ...$filterRules,
        ]);
        $model = $this->modelClass;
        $query = $model::query()->latest();
        if ($search = $validated['search'] ?? null) {
            $query->where(function ($builder) use ($search): void {
                foreach ($this->searchable as $index => $column) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $builder->{$method}($column, 'like', '%'.$search.'%');
                }
            });
        }
        foreach ($this->filterable as $filter) {
            if (isset($validated[$filter]) && $validated[$filter] !== '') {
                $query->where($filter, $validated[$filter]);
            }
        }

        return response()->json($query->paginate($validated['per_page'] ?? 25));
    }

    public function store(Request $request): JsonResponse
    {
        $model = $this->modelClass;
        $record = $model::create($request->validate($this->rules()));
        $this->audit->record(class_basename($model).'.created', $record, [], $request);

        return response()->json(['data' => $record], 201);
    }

    public function show(string $record): JsonResponse
    {
        $model = $this->modelClass;

        return response()->json(['data' => $model::query()->findOrFail($record)]);
    }

    public function update(Request $request, string $record): JsonResponse
    {
        $model = $this->modelClass;
        $instance = $model::query()->findOrFail($record);
        $instance->update($request->validate($this->rules($instance)));
        $this->audit->record(class_basename($model).'.updated', $instance, ['changes' => $instance->getChanges()], $request);

        return response()->json(['data' => $instance->refresh()]);
    }

    public function destroy(Request $request, string $record): JsonResponse
    {
        $model = $this->modelClass;
        $instance = $model::query()->findOrFail($record);
        $this->audit->record(class_basename($model).'.deleted', $instance, [], $request);
        $instance->delete();

        return response()->json(status: 204);
    }
}
