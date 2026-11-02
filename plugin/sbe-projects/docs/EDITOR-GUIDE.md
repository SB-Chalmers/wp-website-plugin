# SBE website – how to add content

> Guide for everyone who adds content. This file is the only copy: it is shown to editors under **Help** in WordPress, so edit it here.

Last updated Oct 15, 2026 · @Arvid

## Overview

The website has three kinds of content, and each shows up in a fixed place automatically. You never need to edit the Home, Posts, Projects or Staff pages to make new content appear.

| Content | Use it for | Where it appears |
| --- | --- | --- |
| Post | News, interviews, articles, announcements. Each post has a category: Post, Interview or Article | Home ("News", latest 9, scroll sideways) and the News page, where visitors can filter by tag |
| Project | Research projects, ongoing or completed | Home ("Ongoing projects", latest 9, scroll sideways) and the Projects page, under Ongoing or Completed |
| Person | Everyone on the staff list | The Staff page, grouped into staff groups such as Researchers, Student assistants and Alumni, under "People involved" on any post, project or resource they are tagged on, and under "Contact persons" on the Contact page if ticked as a contact person |
| Resource | Tools, scripts and datasets with a code repository | The Resources page, as a gallery of icons, names and tags, and in Related content and search results |

## Before you start

Everything is done in the WordPress admin. Add `/wp-admin` to the end of the website address and log in with the account you were given.

- The **menu on the left** has a section for each kind of content: **Posts**, **Projects** and **People**.
- Accounts for people who add content have the **Editor** role. Editors can add, edit and publish everything in this guide (posts, projects, people, resources, categories, tags and staff groups), also what others wrote, but can't change the site design, plugins or settings. (An **Author** account can only publish and edit its own posts, projects, people and resources, and can't manage categories, tags or staff groups.)
- Ask the website administrator (currently Arvid) for an account, or if a menu item described here is missing for you.

**This guide is also inside WordPress:** click **Help** in the left menu (Quick tutorial and Editor guide), or, while writing a post or project, the **?** icon in the top-right corner of the editor (**Help**). It is always the latest version.

**How the editor looks (posts, projects and resources):**

- **On the left** is the page itself, laid out as it will be on the site. Empty fields say what to do: **Write the title here**, **Write your text here…**, and on projects a grey line pointing to **Second title** in the sidebar (it can only be filled in there). It shows the header, the title in the same narrow column, picture, text, and below it the parts the site adds by itself (**Read more** links, **Publications**, **People involved**, the **Go to resource** button, tags). These update as soon as you change the boxes on the right. Only the title, the main picture and the text can be clicked and edited; the rest is the site's design. The **Tags** line and **Related content** at the bottom update too when you add or remove tags, so you can see what readers will find next (with no shared tags, a note shows where Related content would be; on the site that section is then left out).
- **On the right** is the **Add content** sidebar with everything else you fill in: **Main picture**, **Second title** and **Status** (projects), **Type of post** (posts), **Tags**, **People**, **Read more links** (projects), **Publications**, **Gallery icon** and **Repository** (resources) and **SBE sections**. When you click a picture in the text, a **Selected picture** box appears at the top for its description (read aloud to people who can't see it). If the sidebar is closed, click **Add content** in the top-right corner.
- WordPress's own settings (author, web address, template, block styles) are hidden. Administrators can show them with **⋮** (top right) → **Show all WordPress settings**, and switch back with **⋮** → **Use the simple editor**.

## Add a post

Posts are for news, interviews and other articles.

1. In the left menu, go to **Posts → Add New Post**.
2. Click **Write the title here** at the top and type the title.
3. Press Enter (or click **Write your text here…** below the picture) and write the text. Enter starts a new paragraph. To add a picture, heading or list, click the **+** button and pick it from the list (or type `/` and its name, for example `/image`).
4. In the **Add content** sidebar, set the **Main picture** (the featured image). This is the picture shown in the galleries and at the top of the post. In the galleries it is cropped to a wide format, so use a landscape image with the important part near the centre. On the post page itself it is shown whole, uncropped. See *Pictures* below.
5. In the **Add content** sidebar, under **Type of post**, tick what kind of post it is: **Post**, **Interview** or **Article**. If you tick nothing, it becomes a **Post**.
6. Optional: add **Tags** in the **Add content** sidebar (see *Tags and related content* below).
7. Optional: tag the people involved in the **People** box (see *Tag people* below).
8. Optional: add papers in the **Publications** box (see *Publications* below).
9. Click **Publish** in the top-right corner, then **Publish** again to confirm.

The post appears right away on the Home page and the News page, newest first. The post page shows its publish date under the title (projects don't show a date). To give a new post an earlier date (e.g. for an older event), change **Publish: Immediately** in the confirmation panel that opens after the first **Publish** click. To change the date of a published post, ask an administrator.

## Add a project

Every project needs a status, **Ongoing** or **Completed**, which decides where it's listed.

1. In the left menu, go to **Projects → Add New Project**.
2. Click **Write the title here** at the top and type the project title.
3. Write the project description below the title, the same way as a post. Start with a "Project overview" heading and a few paragraphs. For videos or funding details, insert a ready-made section (see *Add extra sections* below).
4. In the **Add content** sidebar:
   - **Second title** (optional): a subtitle shown under the title. Leave it empty if you don't need one. (On the page, a grey line under the title points here until it is filled in; on the site nothing is shown when it is empty.)
   - **Status**: tick **Ongoing** or **Completed**.
   - **Main picture** (featured image): the main picture, shown in the galleries and at the top of the project page. In the galleries it is cropped to a wide format, so use a landscape image with the important part near the centre. On the project page it is shown whole. See *Pictures* below.
   - **Tags** (optional): topics the project is about (see *Tags and related content* below).
   - **People**: tag the people who work on the project (see *Tag people* below).
   - **Read more links** (optional): links to external pages, such as a project website, report or publication. Click **Add link**, then fill in the **Link text** and the **Address (URL)**. Use **Up** and **Down** to change the order, and **Remove** to delete a link.
   - **Publications** (optional): papers from the project (see *Publications* below).
5. Click **Publish**, then **Publish** again to confirm.

Ongoing projects appear on the Home page and under "Ongoing projects" on the Projects page. When a project ends, open it, switch the status to **Completed** and click **Save**. It then moves to "Completed projects" and leaves the Home page.

The project page shows, from top to bottom: title, second title, status, featured image, description, **Read more** links, **Publications**, **People** involved and **Related content**. The Read more, Publications, People involved and Related content sections are hidden when they're empty. If you leave the link text empty, the web address is shown instead.

## Add a resource

A resource is a tool, script or dataset the group shares. It works like a post, plus a link to its repository and a separate **gallery icon**. The Resources page shows the icons, not the feature images, so the gallery looks consistent.

1. In the left menu, go to **Resources → Add New Resource**.
2. Click **Write the title here** at the top and type the resource's name. This is the name shown in the gallery.
3. Write the description below the title, the same way as a post.
4. In the **Add content** sidebar:
   - **Main picture** (feature image): the main picture at the top of the resource page. It is not shown on the Resources page, but it is the picture on the resource's card in **Related content** and search results (without one, the card shows the gallery icon, or the initials).
   - **Gallery icon**: click **Choose icon** and pick or upload an image. Use a square image, ideally a simple logo or symbol on a transparent background (PNG). Without an icon, the gallery shows the resource's initials on a coloured square (a colour from the Home page picture, chosen automatically).
   - **Repository**: paste the repository address, for example `https://github.com/…`. It appears as a blue **Go to resource →** button under the description. Leave it empty to hide the link.
   - **Tags** (optional): topics the resource is about, from the same tag list as posts and projects (see *Tags and related content* below).
   - **People** (optional): the people who made or maintain the resource (see *Tag people* below).
5. Click **Publish**, then **Publish** again to confirm.

The Resources page lists all published resources in alphabetical order, 24 per page. The resource page shows title, feature image, description (including any **Read more links** section, see *Add extra sections* below), the repository link, **Publications**, **People involved** and **Related content** (all hidden when empty).

## Publications

Posts, projects and resources all have a **Publications** box in the **Add content** sidebar. The papers you add there are listed under a **Publications** heading on the page, after the text (and after Read more links), just above People involved. Leave the box empty and the section doesn't appear at all.

**Paste BibTeX (quickest):** copy the BibTeX of one or more papers, for example from research.chalmers.se (the publication's page → Export → BibTeX), Google Scholar (Cite → BibTeX) or Zotero/Mendeley, paste it into **Paste BibTeX** and click **Add from BibTeX**. Several entries at once are fine. The title, authors, year, journal/conference and link (the `url`, or the DOI as a doi.org link) are filled in for you; papers already in the list are skipped. Check the result and click **Edit** to fix anything, e.g. shorten a very long author list.

**One by one:** click **Add publication** and fill in **Title**, **Link** (optional), **Authors**, **Year** and **Journal or conference**. A publication without a title isn't shown.

**Up** / **Down** change the order (shown on the page in this order), **Remove** deletes one. On the page each publication looks like this: the title as a link, then on the next line *Authors (Year). Journal.* in grey.

## Add extra sections (videos, key facts…)

Most posts, projects and resources only need text, a featured image, Read more links and people. When you need more, insert a ready-made **SBE section**:

1. Click the block the section should come **after** (click nothing to add it at the end).
2. In the **Add content** sidebar, scroll to the **SBE sections** panel at the bottom and click the section you want.
3. Replace the example text.

The same sections are also in the **+** (Block inserter, top left) → **Patterns** → **SBE sections**, or type `sbe` in the inserter's search box.

To keep things simple, the **+** for news posts, projects and resources only offers the blocks these sections are built from: paragraph, heading, list, image, video/embed (YouTube and Vimeo), group and quote. Its **Patterns** tab only shows the SBE sections. Pages (Home, People, Contact…) and the Site Editor still have every block.

| Section | What it gives you |
| --- | --- |
| **Video** | A YouTube/Vimeo video: paste the link into the box. Add a caption (the video's title) from the toolbar. |
| **Video section** | A heading, a video and a short paragraph. |
| **Image with caption** | One image with a caption underneath. |
| **Images, one after another** | A heading and two images with captions. Duplicate an image (⋮ → Duplicate) for more. |
| **Publications** | Opens the **Publications** box in Add content (see *Publications* above). Publications are no longer typed into the text. |
| **Key facts** | A light grey box with Funder, Duration and Partners. Delete the lines you don't need. |
| **Project partners** | A heading and a list of partner organisations. |
| **Interview question and answer** | A bold question and the answer. Insert once per question. |
| **Read more links** | A "Read more" heading and a list of links, same look as on project pages. Put it at the very end of the text. On a **project** this button opens the project's own **Read more links** panel instead (fill in the links there). |
| **Event details** | A grey box with Date, Place and Registration. |
| **Quote** | A highlighted quote with the person's name. |

All sections can be used in posts, projects and resources.

After inserting, everything is ordinary text and images, so you can edit, move or delete any part. **Keep everything stacked vertically:** post, project and resource pages are a narrow column, so don't put things side by side (no Columns or Gallery blocks; use *Images, one after another* instead).

**Links to other projects or posts** in the text: select the words, press Ctrl/Cmd+K and type the title of the project or post to find it.

## Categories

Every post has a **category** that says what kind of post it is. The starting categories are **Post**, **Interview** and **Article**. The category is shown in the small grey line under the title in Related content, search results and tag pages (e.g. **Interview · 2 July 2025**). (The News page used to have a category filter; visitors now filter by tag instead, see below.)

- **Choose the category** in the **Type of post** box in the **Add content** sidebar. Tick one. If none is ticked, the post becomes a **Post**.
- **Add, rename or delete categories** under **Posts → Categories** (needs an Editor or Administrator account). Deleting a category moves its posts to **Post**.
- Categories are only for posts. Projects use **Status** (Ongoing / Completed) instead. New categories and statuses are added here, not in the editor.

## Tags and related content

**Tags** are topic keywords, for example "timber" or "life cycle assessment". Posts, projects and resources share the same tag list, so the same tag can be used on all three.

1. While editing a post, project or resource, find the **Tags** box in the **Add content** sidebar.
2. Type a tag and press Enter (or pick an existing one from the suggestions). Repeat for more tags.
3. Click **Save** (or **Publish**).

Reuse existing tags where you can (check the suggestions while typing), so the same topic isn't spelled two ways. All tags are listed, and can be renamed or merged, under **Posts → Tags** (the same list is under **Projects → Tags** and **Resources → Tags**).

Where visitors see tags:

- **Tag filter:** the News, Projects and Resources pages have a row of tags above the gallery: **Tags  All · <the most used tags> · More tags**. Clicking a tag shows only items with that tag (on Projects, both Ongoing and Completed); **All** shows everything again. Only tags that are used on that page's items are listed, so a new tag appears there as soon as a published item uses it.
- **Under every gallery title** (Home, News, Projects, Resources, Related content, search results) in small grey underlined text. They are links: on News, Projects and Resources a tag turns on the filter for that tag; on Home it opens the News or Projects page filtered by it; in Related content and search results it opens the tag page.
- **On the post, project or resource page itself**, as a "Tags: …" line just before Related content. Each tag opens the **tag page** (`/tag/<name>/`): everything with that tag (news, projects and resources) in one gallery, newest first, which can be narrowed to News, Projects or Resources.

A card or page without tags just shows nothing there.

At the bottom of every post, project and resource, a **Related content** row shows other posts, projects and resources that share at least one tag with it, the ones sharing the most tags first, then the newest. It sits in the same narrow column as the text, with smaller cards than the rows on the Home page, scrolls sideways and shows up to 12 items. A grey line under each title says what it is: **Project · Ongoing/Completed**, the post's category and date (e.g. **Interview · 2 July 2025**) or **Resource**. If the item has no tags, or nothing else shares its tags, the row is hidden.

## Tag people on a post, project or resource

Tagged people are shown at the bottom of the post, project or resource under "People involved", with photo, name and role, linking to their staff profile. If nobody is tagged, the section is hidden.

1. While editing the post, project or resource, find the **People** box in the **Add content** sidebar.
2. Click in **Add a person**, start typing a name and click it in the list (or press Enter). Repeat for each person. Each new person is added at the bottom of the list below the box.
3. To change the order, drag a name up or down by its row (the **⠿** handle), or use the **↑** and **↓** buttons next to it.
4. To remove someone, click the red **×** next to their name.
5. Click **Save** (or **Publish** for a new post).

People are shown on the page in the order of the list. Only people already on the staff list can be tagged. If someone is missing, add them first (see *Add a person* below).

## Add a person

Each person on the staff list is one entry under **People**. People don't get their own page on this site. Their name and photo link to their external staff profile instead.

1. In the left menu, go to **People → Add New Person**.
2. Type the person's **full name** at the top.
3. Below the name, fill in the **Profile details** box:
   - **Role**, for example "Professor" or "PhD student". Student assistants can leave it empty: posts and projects then show "Student assistant" (the staff group's default role).
   - **Profile page (external link)**: the full address of their staff page, starting with `https://`.
   - **Research interests**: a sentence or a short list. Only shown on the Staff page.
   - **Order on People page** (optional): a number, for example 1 for the research area lead. People with a number come first in their section, lowest first. Everyone without one follows in alphabetical order. Leave it empty in most cases.
   - **Contact person** (optional): tick **Show on the Contact page** to list them under "Contact persons" on the Contact page, with a small photo, name and role. Contact persons are shown in the same order as on the Staff page.
4. In the boxes on the right:
   - **Profile photo**: click **Set profile photo** and upload or choose a photo. It's shown as a square, so a head-and-shoulders photo works best. Use a sharp, good-quality photo, not a small thumbnail.
   - **Staff groups**: tick the section they belong in, for example **Researchers**, **Student assistants** or **Alumni**.
5. Click **Publish**.

The person now appears on the Staff page and can be tagged on posts and projects. In posts and projects only photo, name and role are shown. On the Staff page, research interests are shown too.

**Only the name is required.** Photo, role, link and research interests can all be left empty, which is common for student assistants and alumni:

- **No photo:** a grey square with their initials is shown instead. If nobody in a Staff page section has a photo (often Alumni), that section becomes a simple list of names without picture boxes.
- **No link:** the name is shown as plain text instead of a link.
- **No role or research interests:** those lines are left out.

## Manage staff groups

Staff groups decide which section of the Staff page a person is listed in. They are never shown as tags on the site.

**Move someone to another group** (for example when a researcher leaves and becomes Alumni):

1. Go to **People → All People**.
2. Hover over the person and click **Quick Edit**.
3. Tick the new group, untick the old one, and click **Update**.

Or open the person and change the **Staff groups** box in the sidebar.

**Rename, add or reorder groups** under **People → Staff groups**:

- **Rename**: hover over a group, click **Edit**, change the name and click **Update**. Names use sentence case: only the first word has a capital letter (*Student assistants*, *Visiting researchers*).
- **Slug**: the short web name of the group (lowercase, words joined with hyphens, e.g. `student-assistants`), shown in the **Slug** column. WordPress makes it from the name when you add a group. It isn't shown to visitors, but the site's styles use it, so keep it when you rename a group.
- **Add**: fill in the name on the left and click **Add New Staff Group**. New groups are added at the **bottom** of the list; move it to where you want it (see below). It appears on the Staff page once someone is in it.
- **Reorder**: the list shows the groups in the same order as on the Staff page. Drag a group by its handle (⠿) in the **Order** column, or click **↑** / **↓** to move it one step. The new order is saved straight away ("Order saved."). If the list is sorted by a column or filtered by a search, the handles are hidden: click **Staff groups** in the menu to get back to the normal list.
- **Default role**: a role shown under the name on posts and projects (and the Contact page) for people in this group who have no **Role** of their own. It is not shown on the Staff page, where the section heading already says it. **Student assistants** and **Former student assistants** have the default role "Student assistant", so you can leave Role empty for them. Type a Role on the person to override it.
- **Show on the People page**: tick boxes for **Photo**, **Role** and **Research interests** decide what the Staff page shows for each person in the group (the name is always shown). All are ticked for a new group. **Alumni** shows name and role only. Without photos, the group becomes a simple list of names. The **Shown on People page** column in the list sums up each group. (A section where nobody has a photo already becomes a list of names automatically.)
- **Hide a group from the Staff page**: tick **Hide on the People page**. People in that group can still be tagged on posts and projects. **External collaborators** (people from other departments or universities who work on our projects) is such a hidden group. Add a collaborator as a person, tick **External collaborators** and fill in their profile link; the photo is optional.

A group with nobody in it is hidden. Someone in two groups is listed in both sections. People with no group are listed at the bottom of the Staff page without a heading.

## Pictures

Most pictures on the site are shown wide, so **use landscape (horizontal) pictures**. The galleries on Home, News and Projects crop every featured image to a wide format, and post and project pages show it whole at the full width of the text.

- **Landscape, not portrait.** An upright picture loses its top and bottom in the galleries and becomes very tall on the post page, pushing the text down. If all you have is an upright photo, crop a landscape part out of it, or use it further down in the text instead.
- **Important part in the middle.** The edges are what gets cut in the galleries.
- **Photos, not posters or logos, as featured images.** Text in a picture gets cropped and is hard to read on phones; put posters, slides and diagrams in the text instead. If a logo is all you have, place it on a wide canvas with plenty of empty space around it.
- **Profile photos and resource icons are square:** a head-and-shoulders photo for people, a simple symbol filling most of the square for resources (PNG with a transparent background).
- **Good quality, not huge files.** Use sharp pictures, but resize big camera photos and large exports (Rhino, Illustrator, InDesign) before uploading; very large files make pages slow and can fail to upload. WordPress makes the smaller versions it needs itself.
- **File types:** JPG for photos, PNG for diagrams, screenshots and logos. SVG can't be uploaded (export as PNG), and iPhone HEIC photos need to be exported as JPG first.
- **Name and describe the file:** give it a descriptive name before uploading (`timber-pavilion-workshop.jpg`, not `IMG_4032.jpg`), and fill in **Alternative text** in the Media Library with a short description of what the picture shows.

To crop a picture, use Preview (Mac) or Photos before uploading, or **Media → Library → Edit Image → Crop** after uploading.

## Keep the site updated (administrators)

Only administrators can install updates, so editors can skip this section. If you notice something broken on the site, tell the website administrator.

### What to install

Install updates, don't skip them. An out-of-date WordPress site is a much bigger risk (it is how most sites get hacked) than an update breaking something. **Dashboard → Updates** shows what is waiting.

| Update | What to do |
| --- | --- |
| WordPress **minor** release, e.g. 7.1.3 → 7.1.4 | Install right away. These are security and bug fixes and almost never break anything. They usually install themselves. |
| WordPress **major** release, e.g. 7.1 → 7.2 (a few per year) | Install within a few weeks, after the checks below. This is the update most likely to affect the site's own features. |
| Plugins (Jetpack, Akismet) | Install. You can turn on **Enable auto-updates** for them on the **Plugins** page. |
| Theme (Twenty Twenty-Five) | Install, then do the quick check below. The site's design settings are stored separately and are kept. |
| The site's own plugin (**SBE Projects**) | Never shows up here. It only changes when whoever maintains the site changes it. |

The live site is hosted on WordPress.com, which normally installs WordPress updates itself. Then your job is the check after the update rather than installing it.

Don't add plugins the site doesn't need. Every plugin is one more thing to keep updated. Delete plugins you stop using (deactivating isn't enough; they still need updates).

### Before a major update

1. **Make sure there is a recent backup.** Check that the hosting has a backup from today (on WordPress.com: **Jetpack → Activity Log / Backup**). If you're not sure, pull the live site into WordPress Studio; that local copy is a full backup too.
2. **Test it on a copy first (recommended).** Pull the live site into WordPress Studio, install the update there and go through the check below. If everything works, update the live site.
3. **Update at a quiet time**, not just before an event or a big news post, so there is time to fix things.

### Check after an update

Open these pages and look for anything missing, empty or out of place:

- **Home:** the picture, the News and Ongoing projects rows (scroll sideways with the arrows).
- **News** (try a tag in the tag filter and the page numbers), **Projects** (Ongoing and Completed), **Resources**, **People**, **Contact**.
- One post, one project and one resource: second title, status, People involved, Read more links and Related content (on the site, and in the editor when changing tags and status before saving).
- **Search** in the menu.
- In the editor: open a post and check the **Add content** sidebar (Main picture, Tags, People, SBE sections), that a person can be tagged and appears under People involved on the page, and the **?** (Help) sidebar.

### If something breaks

- **Don't panic, content isn't lost.** Posts, projects, people and pictures are stored in the database, and a broken feature doesn't delete them. Most problems are one feature misbehaving (a gallery, a filter, an editor panel) while the rest of the site works.
- **Write down what is broken and which update came just before.** That is usually the cause.
- **The whole site shows "There has been a critical error":** WordPress emails the site's admin address a link to **recovery mode**. Log in through that link; it names the plugin that failed, which you can then deactivate.
- **A plugin update caused it (Jetpack, Akismet):** deactivate that plugin to confirm. The site works without both of them. Reactivate it once a fixed version is out.
- **Undo everything:** restore the backup from before the update (WordPress.com: **Jetpack → Activity Log**, restore to a point before the update). Careful: this also undoes posts and changes made after that point, so note them down first.
- **The site's own features broke (galleries, People lists, Related content, search, SBE sections):** that is the SBE Projects plugin and needs a code fix. Pull the site into WordPress Studio, fix and test it there (the **Build notes** under **Help**, for administrators, describe how each part works, which is also what an AI assistant needs to fix it), then push **only the plugin files** to the live site. Pushing the whole database would overwrite posts added on the live site since the pull.
- **No one is available to fix it right away:** the site can stay as it is for a while if the problem is cosmetic. If it is serious, restore the backup and hold off on that update until it's fixed.

## Tips and troubleshooting

- **Save a draft first:** click **Save draft** to keep working later. The editor already shows the page as on the site; **Preview** (top right) opens it in a new tab if you want to be sure.
- **Something is missing from the Home page:** the Home page only shows the latest 9 posts and 9 ongoing projects, in rows you scroll sideways (use the ← → arrows under each row, or swipe). Older ones are still on the News and Projects pages (use the "View all" links on the Home page).
- **A project isn't on the Projects page:** check that **Status** is ticked in the **Add content** sidebar. Projects without a status aren't listed in either gallery. Each gallery shows 12 projects per page; use the arrows or page numbers under the gallery to see older ones. On phones, the cards are shown one below the other. The "Ongoing (n) · Completed (n)" links under the intro jump to each section.
- **A tag is missing from the tag filter:** only tags used by at least one published item on that page are listed. Click **More tags** to see the rest.
- **Related content is missing or shows the wrong things:** check the post's, project's or resource's **Tags**. Only items sharing a tag are shown.
- **Someone can't be tagged:** they need to be added under **People** and published first. For someone outside the group, put them in the **External collaborators** staff group so they don't appear on the Staff page.
- **Change the contact persons:** open the person under **People**, tick or untick **Show on the Contact page** in the Profile details box and click **Update**. The **Contact** column in **People → All People** shows a ✓ for everyone currently ticked. If nobody is ticked, the "Contact persons" section is hidden.
- **Someone isn't on the Staff page:** check that they're published. If they have no staff group, look at the bottom of the page.
- **A resource icon looks too small or too big:** crop the image so the symbol fills most of a square canvas, then use **Replace icon**. SVG files can't be uploaded, so export the icon as PNG.
- **A picture looks blurry:** it was uploaded too small. Replace it with a larger version.
- **A gallery card cuts off heads or the important part:** the picture is upright or the subject is near an edge. Crop it to a landscape picture with the subject in the middle and set it again.
- **A gallery card shows letters instead of a picture:** the post or project has no **Featured image**. The letters are a placeholder (the first letters of the title on a colour picked once from the site palette, the same colours as resources without an icon) so the gallery stays even; set a featured image to replace it.
- **Search:** "Search" at the end of the menu opens a search box that covers the page (Esc or clicking "Search" again closes it). It searches news posts, projects and resources (not pages or people). It looks in titles, text, tags, categories, a project's second title and the names of the **people tagged** on it, so good tags and tagging everyone involved make things easier to find (searching a name shows everything that person is tagged on). Results can be narrowed to News, Projects or Resources with the links above them. A draft or private item never shows up.
- **Removing content:** move it to **Trash** from the list (hover, then **Trash**). It can be restored from Trash for 30 days.
