# Rubik Link Analyzer

**Rubik Link Analyzer** is a WordPress plugin designed to analyze links within site articles. It provides tools to scan, analyze, and display detailed information about links, including internal, external, follow, nofollow, and sponsored links.

**Beware** this is still in alpha, some functionalities won't work!

## Main Features

- **Link Scanning**: Scans links within WordPress articles, identifying internal, external, follow, nofollow, and sponsored links.
- **Result Management**: Displays scan results, including found links, their HTTP status, link type, anchor text, and other relevant details.
- **Advanced Search**: Search by single URL, post title, or ID, with the ability to update link data through ad-hoc scans.
- **Custom Filters**: Filter links by date, link type (follow, nofollow, sponsored), most linked domains, most used anchor texts, and more.
- **Scheduled Scanning**: Set up a cronjob to automatically scan new articles not yet in the database.
- **Plugin Update System**: Automatic plugin update checks via JSON, with the ability to update directly from the WordPress dashboard.

## Requirements

- **WordPress**: Version 5.0 or higher.
- **PHP**: Version 7.4 or higher.

## Installation

1. Download the plugin ZIP file.
2. Access the WordPress admin dashboard.
3. Navigate to **Plugins > Add New**.
4. Upload the ZIP file and click **Install Now**.
5. Activate the plugin after installation.

## Configuration

- Once activated, the plugin adds a new menu called **Link Analyzer** to the WordPress admin panel.
- You can access **Scan**, **Results**, and **Results for Single URL** pages to manage your site's links.

## Usage

### Link Scanning
- Navigate to the **Scan** page to start scanning articles by selecting:
  - All articles.
  - Only articles not yet scanned.
  - Articles published within a specific date range.
- Select custom post types to include in the scan.

### Viewing Results
- Go to the **Results** page to view the last 10 links found and apply filters for deeper analysis.
- Filter links by date, link type (follow, nofollow, sponsored), and more.

### Search by Domain, Title, Anchor, Permalink, HTTP Status, or Post ID
- Use the **Results for Single URL** page to search for a specific link by Permalink, ID, post title, destination domain, anchor text, or HTTP status.
- Update the scan data for a single post by running a dedicated scan through the search.

## Plugin Updates

The plugin includes an automatic update system that allows you to:
- Periodically check for new versions via a JSON file hosted on a remote server.
- Download and install the latest available version directly from the WordPress dashboard.

## Cronjob for Scheduled Scanning

To automate scanning of new articles daily:
- A cronjob is set up to scan new articles not yet in the database every morning at 4:00 AM (CPT: post and page).

## Contributing

- **Bug Reporting**: To report bugs or issues, open an issue on GitHub.
- **Pull Requests**: PRs are welcome! If you want to improve the plugin, feel free to propose your changes.

## License

This project is licensed under the **GPLv2 or later**. Feel free to use and modify it under the terms of the license.

## Author

**Matteo Morreale** - [Website](https://matteomorreale.it)

## Contact

For any questions or requests, feel free to contact me directly via [matteomorreale.it](https://matteomorreale.it).
