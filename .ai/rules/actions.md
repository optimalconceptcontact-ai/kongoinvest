---
paths:
  - 'app/Actions/**'
---

# Actions

## forceFill for non-mass-assignable fields
Models deliberately exclude ownership/state fields (user_id, project_id, status, visibility, published_at, archived_at...) from $fillable so requests can never set them. Actions that must write these fields use `$model->forceFill([...])->save()` — never `update([...])` which silently ignores non-fillable keys. create() with relationship keys still requires those keys to be fillable.
