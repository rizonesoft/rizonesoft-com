# Astra Theme - Technical Bug Report

## Title
Multiple React 18 Deprecation Warnings and Invalid Props in Customizer Controls

## Summary
The Astra theme customizer (`wp-content/themes/astra/inc/customizer/extend-custom-controls/`) generates numerous console warnings related to React 18 deprecation and invalid DOM properties when using the WordPress Customizer.

## Environment
- **Astra Version**: 4.11.13 (based on `index.js?ver=4.11.13`)
- **WordPress Version**: 6.8.3
- **React Version**: 18.x (WordPress core)
- **PHP Version**: [Your PHP version]
- **Browser**: Chrome/Edge (Chromium-based)
- **Theme Type**: Child theme based on Astra

## Severity
**Medium** - Does not break functionality but:
- Clutters console with 50+ warnings during normal customizer use
- Indicates use of deprecated APIs that will break in future React versions
- May cause performance degradation
- Creates poor developer experience

## Issues Identified

### 1. React 18 Deprecation Warnings (High Frequency)
**Console Output:**
```
Warning: ReactDOM.render is no longer supported in React 18. Use createRoot instead. 
Until you switch to the new API, your app will behave as if it's running React 17. 
Learn more: https://reactjs.org/link/switch-to-createroot
```

**Location:** 
- File: `wp-content/themes/astra/inc/customizer/extend-custom-controls/build/index.js?ver=4.11.13`
- Function: `renderContent()`
- Occurs: ~50+ times when opening customizer controls

**Root Cause:**
The customizer controls are using the deprecated `ReactDOM.render()` method instead of the React 18 `createRoot()` API.

### 2. Invalid DOM Property: viewbox
**Console Output:**
```
Warning: Invalid DOM property `viewbox`. Did you mean `viewBox`?
    at svg
    at span
    at div
    at div
    at label
    at g (https://rizonesoft.com/wp-content/themes/astra/inc/customizer/extend-custom-controls/build/index.js?ver=4.11.13:1:1646455)
```

**Issue:** SVG elements are using lowercase `viewbox` instead of camelCase `viewBox`.

### 3. Invalid DOM Property: stokeWidth
**Console Output:**
```
Warning: React does not recognize the `stokeWidth` prop on a DOM element. 
If you intentionally want it to appear in the DOM as a custom attribute, 
spell it as lowercase `stokewidth` instead.
```

**Issue:** Typo in SVG property - should be `strokeWidth` not `stokeWidth`.

## Steps to Reproduce

1. Install Astra theme (version 4.11.13)
2. Navigate to WordPress Admin → Appearance → Customize
3. Open browser DevTools Console (F12)
4. Click on any customizer section (Header, Footer, Colors, etc.)
5. Expand any control panel
6. Observe console warnings

## Expected Behavior

- No console warnings or errors
- Use React 18 `createRoot()` API
- Proper camelCase SVG attributes (`viewBox`, `strokeWidth`)
- Clean developer console experience

## Actual Behavior

- 50+ React deprecation warnings per customizer session
- Invalid DOM property warnings for SVG elements
- Console is flooded making debugging difficult
- Performance overhead from React compatibility layer

## Suggested Fixes

### Fix 1: Migrate to React 18 createRoot API
**Current Code Pattern:**
```javascript
ReactDOM.render(
    <Component />,
    container
);
```

**Should Be:**
```javascript
import { createRoot } from 'react-dom/client';

const root = createRoot(container);
root.render(<Component />);
```

### Fix 2: Correct SVG Attributes
**Current:**
```jsx
<svg viewbox="0 0 24 24">
  <path stokeWidth="2" />
</svg>
```

**Should Be:**
```jsx
<svg viewBox="0 0 24 24">
  <path strokeWidth="2" />
</svg>
```

## Additional Context

### Files Likely Affected
- `wp-content/themes/astra/inc/customizer/extend-custom-controls/src/`
- Build output: `wp-content/themes/astra/inc/customizer/extend-custom-controls/build/index.js`

### Related Console Warnings
Also seeing these related issues:
- `deprecated.js:115` - `position` prop in `wp.components.tooltip` is deprecated
- Multiple forced reflow warnings causing performance issues

## References

- React 18 Migration Guide: https://react.dev/blog/2022/03/08/react-18-upgrade-guide
- React DOM Attributes: https://react.dev/reference/react-dom/components/common#common-props
- SVG in React: https://developer.mozilla.org/en-US/docs/Web/SVG/Attribute/viewBox

## Workaround (Temporary)

Users can suppress console warnings in production by adding to child theme's `functions.php`:

```php
// Temporary workaround - does not fix root cause
add_action('wp_head', function() {
    if (!WP_DEBUG && !is_customize_preview()) {
        echo "<script>window['__REACT_DEVTOOLS_GLOBAL_HOOK__'] = { isDisabled: true };</script>\n";
    }
}, 1);
```

## Impact on Users

- **Developers**: Difficult to debug due to console noise
- **End Users**: Potential performance degradation in customizer
- **Future Compatibility**: Will break when WordPress updates to React 19+

## Priority Recommendation

**Medium-High** - Should be addressed in next minor release to ensure future WordPress compatibility.

---

**Reported By:** [Your Name/Company]
**Date:** October 20, 2025
**Contact:** [Your Email]
