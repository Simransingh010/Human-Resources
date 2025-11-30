# Implementation Plan

- [x] 1. Create RouterWrapper Livewire component


  - Create `app/Livewire/Panel/RouterWrapper.php` with lean logic
  - Handle route component and wire fallback modes
  - Listen for wireSelected events
  - Use stable md5-based keys
  - _Requirements: 1.1, 1.2, 1.3, 9.1, 9.2_



- [ ] 2. Create RouterWrapper blade view
  - Create `resources/views/livewire/panel/router-wrapper.blade.php`
  - Use @try/@catch for error handling


  - Show friendly error message for invalid components
  - _Requirements: 1.1, 1.2, 10.1, 10.4_

- [x] 3. Create session initialization middleware


  - Create `app/Http/Middleware/InitializeSessionDefaults.php`
  - Initialize firm_id, panel_id, defaultwire if not set


  - Keep it minimal - no business logic mutations
  - _Requirements: 7.1, 7.2, 7.3_



- [ ] 4. Register middleware in Kernel
  - Add middleware alias in `app/Http/Kernel.php`
  - _Requirements: 7.4_



- [ ] 5. Add universal screen route
  - Add single `/screen/{component}` route to `routes/web.php`
  - Support query params for module/app context



  - _Requirements: 5.1, 5.4_

- [ ] 6. Create panel-screen layout view
  - Create `resources/views/layouts/panel-screen.blade.php`
  - Use existing sidebar layout
  - _Requirements: 2.1, 2.2_

- [ ] 7. Update sidebar layout to use RouterWrapper
  - Modify `resources/views/components/layouts/app/sidebar.blade.php`
  - Replace MainContent with RouterWrapper
  - Pass route component parameter
  - _Requirements: 2.1, 6.1, 6.2_

- [ ] 8. Add isRouteBased method to MenuCoordinator
  - Add single `isRouteBased()` method to `app/Services/MenuCoordinator.php`
  - Empty array initially for gradual migration
  - _Requirements: 4.1, 4.2, 4.3_
