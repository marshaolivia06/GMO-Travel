export const navItems = [
  { to: 'user-management', label: 'User Management', icon: 'ri-team-line' },
  { label: 'Master Data', icon: 'ri-database-2-line', children: [{ to: 'master-management', label: 'Master Management' }, { to: 'master-advance', label: 'Master Advance' }] },
  { to: 'travel-order', label: 'Travel Order', icon: 'ri-flight-takeoff-line' },
  { to: 'annual-leave', label: 'Annual Leave', icon: 'ri-calendar-check-line' },
]