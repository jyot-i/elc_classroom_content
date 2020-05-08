# Classroom Package

What will module do?

Create content type - level and module
Create taxonomies - classroom tabs, module category etc
Create views - level and module_lising

## Usage

1. After installing the module visit `/admin/structure/taxonomy/manage/classroom_tabs/overview` to
add a term.

2. Visit /node/add/module and create module content.

3. visit /node/add/level and create level content.

4. visit /api/v1/levels/{classroom_tabs_term_id} to get levels.

5. visit /api/v1/levels/{level_id} to get respective modules.

## Dependencies

Currently the following modules are required to install this package:

  * [conditional_fields]
  * [plupload_widget]
  * [plupload]