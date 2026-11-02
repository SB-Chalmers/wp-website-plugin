# Setting up a new site

This takes about 15 minutes, plus the time to add your own content. You need administrator access to a WordPress site.

## 1. Get a WordPress site

Any WordPress site works if it lets you upload plugins and themes:

- **To try it first**, install [WordPress Studio](https://developer.wordpress.com/studio/) (free, Mac and Windows) and create a local site. You can push it to a live host later.
- **For the live site**, use whatever hosting your division has. Check that it allows uploading plugins and themes. On WordPress.com this needs a plan with plugin support.

A brand-new, empty site is easiest. On an existing site, nothing is overwritten (see step 4), but your current theme gets replaced.

## 2. Make the two zip files

Download this repository (**Code → Download ZIP** on GitHub) and unzip it. Then zip the two folders separately:

- `plugin/sbe-projects` → `sbe-projects.zip`
- `theme/sbe-theme` → `sbe-theme.zip`

On a Mac, right-click the folder and choose **Compress**. On Windows, choose **Send to → Compressed (zipped) folder**. The zip must contain the folder itself, not only its contents.

## 3. Install the plugin, then the theme

1. **Plugins → Add New Plugin → Upload Plugin**, choose `sbe-projects.zip`, **Install Now**, then **Activate**.
2. **Appearance → Themes → Add New Theme → Upload Theme**, choose `sbe-theme.zip`, **Install Now**, then **Activate**.

The theme needs **Twenty Twenty-Five**, which comes with WordPress. If it has been deleted, install it first from **Appearance → Themes → Add New Theme**.

Install the plugin first. When activated, it creates the project statuses (Ongoing, Completed), the staff groups, the post categories, the Resources page and the search template.

## 4. Create the pages

A blue notice appears at the top of the dashboard. Click **Create the pages**. This:

- creates **Home**, **News**, **Projects**, **People** and **Contact** with their galleries in place;
- puts the menu in order: Home, News, Projects, Resources, People, Contact, then Search;
- makes Home the front page;
- switches to date-based links (`/2026/11/02/post-name/`, as on sb.chalmers.se) if the site still uses plain `?p=123` links.

Pages that already exist with the same address (`/projects/`, `/contact/` …) are left alone. If you clicked "Not now", or want to run the setup again, delete the option `sbe_theme_setup_done` or create the pages by hand. The layouts are in `theme/sbe-theme/starter-pages/`.

Then delete the WordPress sample content: the page "Sample Page" and the post "Hello world!".

## 5. Make it yours

| What | Where |
| --- | --- |
| Site title (shown in the header and the footer copyright line) | Settings → General → Site Title |
| Logo (left of the title) | Appearance → Editor → Patterns → Header, then click the logo placeholder and upload. Use the Chalmers logo from the Chalmers brand pages |
| Footer: address, directions, opening hours | Appearance → Editor → Patterns → Footer |
| Home: intro sentence and the large picture on top | Pages → Home. The picture is the page's **featured image** (Page settings → Featured image). Use a wide picture, about 2560 px |
| Projects and Resources intro text | Pages → Projects, Pages → Resources |
| Contact page | Pages → Contact. Mark people as contact persons in their profiles (People → edit → "Contact person") to list them there |
| Staff groups | People → Staff groups |

The design (fonts, sizes, colours, spacing) can be changed in **Appearance → Editor → Styles**. Changes are stored in your database, not in the theme files.

## 6. Add content

- **People** first, so they can be tagged on news and projects.
- Then **Projects** (set the status in the sidebar), **News** (Posts) and **Resources**.
- Editors find a guide under **Help** in the admin menu. It is written for sb.chalmers.se, so page names may differ slightly from yours.

Pictures: landscape, the subject in the middle, under 1 MB. Items without a picture get a coloured placeholder with their initials.

## SBE-specific parts you can change or remove

- `plugin/sbe-projects/includes/redirects.php` redirects two old SBE addresses (`/lcaphdforum/` and `/mrstudio/`). It only acts when those addresses don't exist, so it is harmless. Empty the list in `sbe_redirect_map()` if you like.
- `plugin/sbe-projects/docs/` describes the SBE site. Adapt it to yours: the Help page shows these files.
- The People page, staff groups and categories use SBE's names (Researchers, Student assistants, Alumni; Post, Article, Interview). Rename them in WordPress.

## Updating later

When a new version is published here, make new zips and upload them the same way. WordPress asks whether to **Replace current with uploaded**; say yes. Your content and your Site Editor changes are kept.

A template or header you edited in the Site Editor overrides the theme's file. It won't change when you update the theme. To go back to the theme's version, use **Reset** on that template in the Site Editor.

## If something looks wrong

- **Some pages have no header or footer** (search, tag pages, resources): the plugin is older than 1.26.1. Install the version from this repository.
- **The Projects galleries are empty although there are projects**: each gallery filters on a project status. Open Pages → Projects, select the gallery (Query Loop) and set the filter under **Filters → Project status** to Ongoing and Completed respectively.
- **Everything looks like plain Twenty Twenty-Five**: check that **SBE Theme** is the active theme, not Twenty Twenty-Five.
