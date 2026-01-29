@extends('layouts.app')
@section('title','Classes')

@section('actions')
  <a href="{{ route('classes.create') }}" class="btn btn-primary">
    <i class="bi bi-plus-lg me-1"></i> Add Class
  </a>
@endsection

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="fw-semibold">Classes</span>
    <a href="{{ route('classes.create') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>Add</a>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Name</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($classes as $c)
            <tr>
              <td class="fw-semibold">{{ $c->name }}</td>
              <td class="text-end">
                <a href="{{ route('classes.edit',$c) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                <form action="{{ route('classes.destroy',$c) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this class?')">
                  @csrf @method('DELETE')
                  <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="2" class="text-center text-muted py-4">No classes yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if(method_exists($classes,'links'))
    <div class="card-footer">{{ $classes->links() }}</div>
  @endif
</div>
@endsection
