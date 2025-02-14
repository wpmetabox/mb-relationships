**MB Relationships** helps you create many-to-many relationships between posts, pages or any custom post type. The plugin is lightweight and optimized for database and query performance.

The plugin allows you to create connections from posts to posts, posts to pages and so on. Then you can perform corresponding queries to retrieve posts that are connected to or from given posts.

> **Meta Box Lite**<br>
> We recommend using [Meta Box Lite](https://metabox.io/lite/), a feature-rich free UI version of Meta Box that provides UI and all free features for managing custom fields and dynamic content on WordPress, including post types, taxonomies, custom fields, and relationships.

It supports reciprocal and bi-directional relationships.

### Why Do You Need Posts To Posts Relationships In WordPress?

Post relationships is a missing part in WordPress. The only "built-in" way that mimic the post relationship in WordPress is the `post_parent` for pages where you can create many child pages of a page (a one-to-many relationship). Unfortunately, that's available for pages and hierarchical post types only. Besides, it's not many-to-many relationship.

Below are some examples of posts to posts relationships that might help you see the benefit of this feature:

#### Creating Related Posts In WordPress

The simplest example is to manually create related posts in your WordPress website. When you edit a post, you can select posts that have similar or related content and display them in the frontend for further reading.

You can also query backward: displaying posts that link to the being read post as a reference to provide more information to your readers. With this, you don't need a WordPress related posts plugin anymore.

#### Example: Events And Bands

Suppose you have two custom post types, event and band, where:

- In each event there may be multiple bands, and
- Each band can participate in many events.

If people want to buy tickets, they could search for events in their location and see what bands are playing on a given date, or they could search for bands they like and see what date they are playing near their location.

In this example, we have created many-to-many relationships between events and bands.

### Bi-directional relationships

**MB Relationships** allows you to create bi-directional connections. You will be able to query back and forth without any problem.

The data is stored in the database as a pair of (from_id, to_id), thus making it independent from either side.

Besides, for each side, there's a meta box that shows what are connected from/to. So you don't have to worry about the direction of the connection anymore.

### An Alternative For The Posts 2 Posts Plugin (P2P Plugin)

**MB Relationships** is very much inspired by the popular plugin [Posts 2 Posts](https://wordpress.org/plugins/posts-to-posts/) which is not maintained anymore. We have taken the idea and made some improvements. The codebase and data structure is very similar.

## Plugin features

- **Simple APIs**: the plugin provides simple APIs for registering relationships and retrieving connected items. It integrates with existing WordPress APIs such as `WP_Query`,` get_terms` and `get_users`. See [documentation](https://docs.metabox.io/extensions/mb-relationships/) for more information.
- **REST API**: the plugin provides a REST API to manage relationships programatically. See [documentation](https://docs.metabox.io/extensions/mb-relationships/#rest-api) for more information.
- Uses **custom relationship table** to store relationships. That helps optimize the database storage and query performance.
- You can **create relationships between any kind of content** in WordPress: posts to posts, posts to users, etc. For posts to terms and posts to users, it's required the [MB Term Meta](https://metabox.io/plugins/mb-term-meta/) and [MB User Meta](https://metabox.io/plugins/mb-user-meta/).
- Supports creating **reciprocal relationships** (posts-posts, users-users, ...).
- Supports creating **bi-directional relationships** and easily query them.
- Display connected items easily with **shortcode**.
- Extremely **lightweight and fast**.

## Plugin Links

- [Project Page](https://metabox.io/plugins/mb-relationships/)
- [Github Repo](https://github.com/wpmetabox/mb-relationships/)

This plugin is a free extension of [Meta Box](https://metabox.io) plugin, which is a powerful, professional solution to create custom fields and custom meta boxes for WordPress websites. Using **MB Relationships** in combination with [other extensions](https://metabox.io/plugins/) will help you manage any content types in WordPress easily and make your website more professional.

## You might also like

If you like this plugin, you might also like our other WordPress products:

- [Meta Box](https://metabox.io) - The most powerful WordPress plugin for creating custom post types and custom fields.
- [Slim SEO](https://wpslimseo.com) - A fast, lightweight and full-featured SEO plugin for WordPress with minimal configuration.
- [Slim SEO Schema](https://wpslimseo.com/products/slim-seo-schema/) - An advanced, powerful and flexible plugin to add schemas to WordPress.
- [Slim SEO Link Manager](https://wpslimseo.com/products/slim-seo-link-manager/) - Build internal link easier in WordPress with real-time reports.
- [GretaThemes](https://gretathemes.com) - Free and premium WordPress themes that clean, simple and just work.
- [Auto Listings](https://wpautolistings.com) - A car sale and dealership plugin for WordPress

## Installation

You need to install [Meta Box](https://metabox.io) plugin first

- Go to Plugins | Add New and search for Meta Box
- Click **Install Now** button to install the plugin
- After installing, click **Activate Plugin** to activate the plugin

Install **MB Relationships** extension

- Go to **Plugins | Add New** and search for **MB Relationships**
- Click **Install Now** button to install the plugin
- After installing, click **Activate Plugin** to activate the plugin
