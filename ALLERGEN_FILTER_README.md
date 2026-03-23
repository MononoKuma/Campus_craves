# Allergen Filter Documentation

## Overview
The allergen filter system allows users to:
1. Set their personal allergen preferences in their profile
2. Filter products based on their allergens (safe vs unsafe)
3. Search for products containing specific allergens
4. See visual indicators for allergen safety on product cards

## Features Implemented

### 1. User Profile Allergen Management
- Location: `public/profile.php`
- Users can select from 12 common allergens:
  - Nuts, Dairy, Gluten, Eggs, Soy, Shellfish, Sesame, Fish, Peanuts, Wheat, Tree Nuts, Milk
- Allergens are stored as JSON in the database

### 2. Product Browsing with Allergen Filtering
- Location: `src/views/products/list.php`
- Three types of allergen filtering:
  - **All Products**: Shows all products (default)
  - **Safe for Me**: Only shows products that don't contain user's allergens
  - **Contains My Allergens**: Only shows products that contain user's allergens
- **Specific Allergen Filter**: Check boxes to find products containing specific allergens

### 3. Visual Allergen Indicators
- **Green Badge**: "✅ Safe for You" - Product doesn't contain user's allergens
- **Red Badge**: "⚠️ Contains Your Allergens" - Product contains user's allergens
- **Disabled Add to Cart**: Users cannot add products containing their allergens to cart

### 4. Smart Filtering Logic
- Combines user allergen preferences with specific allergen selection
- Properly handles empty allergen arrays
- Rating and price filters work alongside allergen filters

## Technical Implementation

### Database Schema
```sql
-- Users table
allergens JSON NULL COMMENT 'Array of allergens for user dietary preferences'

-- Products table  
allergens JSON NULL COMMENT 'Array of allergens for filtering'
```

### Key Functions
- `User::getUserAllergens($userId)` - Gets user's allergen preferences
- `User::updateAllergens($userId, $allergens)` - Updates user's allergen preferences
- Filtering logic in `src/views/products/list.php` lines 34-77

### Allergen Standardization
All allergen names use lowercase keys with proper display labels:
```php
$allergenOptions = [
    'nuts' => 'Nuts',
    'dairy' => 'Dairy',
    'gluten' => 'Gluten',
    // ... etc
];
```

## User Experience
1. **First Time**: User sets allergens in profile
2. **Browsing**: Products show safety badges
3. **Filtering**: Can filter to safe products or find specific allergens
4. **Protection**: Cannot accidentally purchase allergen-containing products

## Testing
Use `test_allergen_filter.php` to verify functionality when database is available.

## CSS Classes
- `.allergen-safe` - Green safety indicator
- `.allergen-warning` - Red warning indicator  
- `.piston-button.small.disabled` - Disabled add to cart button

The system provides comprehensive allergen protection while maintaining a smooth shopping experience.
