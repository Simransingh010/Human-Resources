# Requirements Document

## Introduction

This document defines the requirements for implementing a hybrid routing system in the Laravel Livewire application. The system will allow both the existing dynamic wire-based navigation (using `$selectedWire` and Livewire events) and new URL-based routing to coexist. This enables gradual migration of screens from the dynamic system to proper URL routes while maintaining full backward compatibility with the existing sidebar, topbar, and menu context.

## Glossary

- **Wire**: A Livewire component identifier string (e.g., `hrms.onboard.employees`) used to dynamically load components
- **MenuCoordinator**: The service class that manages app, module, and wire selection state via session
- **MainContent**: The Livewire component responsible for rendering the currently selected wire/component
- **RouterWrapper**: A new Livewire component that will handle dual-mode content rendering (route-based or wire-based)
- **Dynamic Navigation**: The existing system where clicking menu items dispatches Livewire events to swap components without URL changes
- **Route-based Navigation**: Standard Laravel routing where each screen has a unique URL
- **Hybrid Mode**: The combined system supporting both navigation methods simultaneously

## Requirements

### Requirement 1: Router Wrapper Component

**User Story:** As a developer, I want a wrapper component that can render either route-based or wire-based content, so that I can gradually migrate screens without breaking existing functionality.

#### Acceptance Criteria

1. WHEN the RouterWrapper component receives a route component parameter, THE RouterWrapper SHALL render the specified Livewire component directly.
2. WHEN the RouterWrapper component receives no route component parameter, THE RouterWrapper SHALL fall back to rendering the component specified by the session's selectedWire value.
3. THE RouterWrapper SHALL maintain access to the current MenuCoordinator context (selectedAppId, selectedModuleId, selectedWire) regardless of which rendering mode is active.
4. WHEN a route-based component is loaded, THE RouterWrapper SHALL preserve the sidebar and topbar state without requiring re-initialization.

### Requirement 2: Layout Compatibility

**User Story:** As a developer, I want route-based screens to use the same layout as wire-based screens, so that users experience consistent navigation regardless of how a screen is loaded.

#### Acceptance Criteria

1. THE System SHALL render route-based components within the existing sidebar layout (leftmenu, topbar, topmenu, main-content area).
2. WHEN a user navigates to a route-based screen, THE System SHALL display the same sidebar, topbar, and topmenu components as wire-based screens.
3. THE System SHALL support the `wire:navigate` attribute for SPA-like transitions between route-based screens.
4. WHEN switching between route-based and wire-based screens, THE System SHALL maintain visual consistency without layout flickering.

### Requirement 3: Menu Context Synchronization

**User Story:** As a user, I want the sidebar to reflect the correct app and module selection when I navigate to a route-based screen, so that I always know where I am in the application.

#### Acceptance Criteria

1. WHEN a route-based screen is loaded, THE System SHALL allow optional synchronization of the MenuCoordinator state (selectedAppId, selectedModuleId) via route parameters or component initialization.
2. THE System SHALL provide a mechanism for route-based components to declare their associated app and module IDs.
3. WHEN a user clicks a sidebar module while on a route-based screen, THE System SHALL navigate to the appropriate wire or route based on the module configuration.
4. IF a route-based screen does not specify app/module context, THEN THE System SHALL preserve the previously selected context.

### Requirement 4: Gradual Migration Support

**User Story:** As a developer, I want to migrate individual screens from wire-based to route-based navigation without affecting other screens, so that I can incrementally improve the application.

#### Acceptance Criteria

1. THE System SHALL support mixed navigation where some menu items use `wire:click` for dynamic loading and others use `href` with `wire:navigate` for route-based loading.
2. WHEN a menu item is configured with a route, THE System SHALL render it as a link with `wire:navigate` attribute.
3. WHEN a menu item is configured with only a wire, THE System SHALL render it with `wire:click` for dynamic loading.
4. THE System SHALL allow the MenuCoordinator to store both wire identifiers and route names for components.

### Requirement 5: URL State Management

**User Story:** As a user, I want route-based screens to have bookmarkable URLs, so that I can share links and use browser navigation.

#### Acceptance Criteria

1. WHEN a user navigates to a route-based screen, THE System SHALL update the browser URL to reflect the current screen.
2. WHEN a user uses browser back/forward buttons on route-based screens, THE System SHALL navigate to the correct screen.
3. WHEN a user bookmarks a route-based screen URL and returns later, THE System SHALL load the correct screen with appropriate context.
4. THE System SHALL support route parameters for screens that require dynamic data (e.g., `/employees/{id}`).

### Requirement 6: Backward Compatibility

**User Story:** As a user, I want all existing wire-based screens to continue working exactly as before, so that the hybrid system doesn't disrupt my workflow.

#### Acceptance Criteria

1. THE System SHALL maintain full functionality of all existing wire-based navigation without modification to existing Livewire components.
2. WHEN the RouterWrapper falls back to wire-based rendering, THE System SHALL behave identically to the current MainContent component.
3. THE System SHALL preserve all existing Livewire event dispatching (wireSelected, moduleSelected) for wire-based navigation.
4. IF the hybrid routing implementation fails, THEN THE System SHALL gracefully fall back to the existing wire-based system.

### Requirement 7: Session State Initialization on Direct URL Access

**User Story:** As a user, I want to access route-based screens directly via URL (bookmark/refresh) without encountering errors, so that I can share links and use browser navigation reliably.

#### Acceptance Criteria

1. WHEN a user accesses a route-based screen directly via URL, THE System SHALL ensure panel_id and firm_id session values are initialized before component rendering.
2. WHEN a user accesses a route-based screen directly via URL, THE System SHALL initialize MenuCoordinator state with sensible defaults if not already set.
3. IF session('defaultwire') is not set during direct URL access, THEN THE System SHALL set a fallback default wire value to prevent null reference errors.
4. THE System SHALL use middleware or boot logic to ensure critical session values (firm_id, panel_id) are available for all authenticated routes.

### Requirement 8: Topmenu Synchronization for Route-Based Screens

**User Story:** As a user, I want the top menu buttons to reflect the correct module context when I navigate to a route-based screen, so that I can switch between related screens easily.

#### Acceptance Criteria

1. WHEN a route-based screen loads, THE System SHALL provide a mechanism to dispatch moduleSelected event to synchronize Topmenu.
2. THE System SHALL allow route-based components to declare their associated moduleId for automatic Topmenu synchronization.
3. WHEN Topmenu receives a moduleSelected event from a route-based screen, THE System SHALL load the correct module wires/buttons.
4. IF a route-based screen does not declare a moduleId, THEN THE System SHALL preserve the current Topmenu state without modification.

### Requirement 9: Component Key Management

**User Story:** As a developer, I want Livewire components to have unique, stable keys during navigation, so that component state is properly managed without hydration errors.

#### Acceptance Criteria

1. THE RouterWrapper SHALL generate unique component keys that differentiate between route-based and wire-based rendering modes.
2. WHEN switching from wire-based to route-based navigation, THE System SHALL use distinct keys to force proper component re-initialization.
3. THE System SHALL avoid using session values directly in component keys to prevent key collisions during rapid navigation.
4. WHEN using wire:navigate between route-based screens, THE System SHALL ensure proper component lifecycle management.

### Requirement 10: Error Handling and Graceful Degradation

**User Story:** As a user, I want the application to handle navigation errors gracefully, so that I'm not left with a broken screen.

#### Acceptance Criteria

1. IF a route-based component class does not exist, THEN THE System SHALL display a user-friendly error message or redirect to a safe default screen.
2. IF MenuCoordinator returns invalid wire identifier, THEN THE System SHALL fall back to the default wire without crashing.
3. THE System SHALL log navigation errors for debugging while presenting a clean interface to users.
4. WHEN a Livewire component fails to load, THE System SHALL provide a retry mechanism or navigation option to return to a working state.
