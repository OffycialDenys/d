# TASK 3 - Phase 2 Refactor TODO (Investment Marketplace / Orders / Dashboard)

## Step 1: Create a shared “Owned Investment Details” view
- [x] Create `pages/user/owned-investment-details.php`
  - [x] Render full owned investment details (from marketplace plan + user's owned order data)
  - [x] Include Simulated Growth Performance ONLY here
  - [x] Include actions allowed in spec for owned investments (Buy More, Sell, Asset Liquidation, Confirm Order as applicable)
  - [x] Ensure Sell/Liquidation is NOT present anywhere else after this step

## Step 2: Make `investment-details.php` Marketplace-only (pre-purchase)
- [x] Refactor `pages/user/investment-details.php`
  - [x] Keep: investment specs/info + calculator + Buy flow + checkout modal
  - [x] Remove: simulated growth section (owned-only), Sell modal/button, owned history (“My Investments/Order History”), any liquidation/confirmation/audit UI for owned assets

## Step 3: Wire Orders list to open owned details
- [x] Update `pages/user/orders.php`
  - [x] Add “View Details” action per order row that links to `owned-investment-details.php`
  - [x] Add Liquidate action so Orders provides full Sell/Liquidation/Confirm management

## Step 4: Wire Dashboard holdings cards to open owned details
- [x] Update `pages/user/dashboard.php`
  - [x] Make each holdings card clickable (link to `owned-investment-details.php`)
  - [x] Remove duplicated simulated math/hardcoded progress; rely on owned details view

## Step 5: Ensure JS bindings still work
- [x] `assets/js/app.js` (no change needed):
  - [x] interactive chart is used only in owned details (`[data-interactive-chart]` guard)
  - [x] checkout modal logic remains attached to buy flow on marketplace details only

## Step 6: Validate behavior against checklist
- [x] Marketplace has only Buy + info (no Sell/liquidation/order status/profit tracking widgets)
- [x] Orders contains management (Sell/Liquidation/Confirm) and “View Details”
- [x] Dashboard holdings link to the exact same owned details view
- [x] Simulated Growth exists only inside owned details
- [x] No console errors / JS errors (verified: fixed a fatal parse error in `includes/services/platform.php` that broke the whole app)

### Blocker fixed
- `includes/services/platform.php` had orphaned duplicate code after `reject_deposit()`
  (a stray `}` + fallback `flash/redirect`) causing `PHP Parse error: Unmatched '}'`.
  Removed the dead block; all PHP files now lint clean.
