<x-crud-actions :edit-url="route('users.edit', $user)" :delete-url="route('users.destroy', $user)" record-label="user" :record-name="$user->name" />
