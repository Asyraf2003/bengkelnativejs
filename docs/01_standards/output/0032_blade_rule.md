# P2 - Blade Rule

## Purpose
Keep Blade files clean and consistent, and do not turn them into the place where logic that belongs elsewhere is hidden.

## Mandatory Rule
- Avoid inline PHP blocks in Blade.
- Views should only render data prepared by the correct flow.
- Do not place core domain decisions in Blade.

## Preferred Practice
- Prepare data before the view is rendered.
- Use Blade for presentation, not for moving use-case or domain responsibility around.

## Forbidden Behavior
- Do not push main domain branching logic into Blade.
- Do not use Blade as a shortcut for gaps in the application flow.
