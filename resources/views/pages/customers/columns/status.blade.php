<x-badge :type="$customer->is_active ? 'success' : 'neutral'">
  {{ $customer->is_active ? 'Active' : 'Inactive' }}
</x-badge>
