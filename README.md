# KeyHelp GitHub Release Downloads Widget

A lightweight GitHub release download tracker widget for the KeyHelp admin dashboard (`admin_dashboard`), fetching live release and asset download statistics via a small PHP backend (`plugin_stats.php`) configured through `download_box.conf`.

<img width="826" height="543" alt="Screenshot 2026-11-22 142719" src="https://github.com/user-attachments/assets/dab1cc48-8ad4-4d01-a50d-dc9e6c5d7c45" />

> [!IMPORTANT]
>
> **Requirements**
>
> - **Panel:** KeyHelp admin dashboard (`admin_dashboard`) with `jQuery` available.
> - **PHP & Outbound HTTPS:** PHP endpoint (`plugin_stats.php`) reachable under `dashboard-layout/` with outbound internet access (`curl` / `allow_url_fopen`) to `api.github.com`.
> - **Permissions:** Directory and files owned by `keyhelp:keyhelp` so KeyHelp can serve the frontend script and execute `plugin_stats.php`.

***

## Features

- Responsive 3-column grid displaying total downloads, latest release assets, and per-file download counts for each configured GitHub repository.
- Compact floating popup for viewing older releases and their asset counts without stretching dashboard column heights.
- Simple `.conf`-based repository configuration (`download_box.conf`) without touching JavaScript or PHP code.
- Automatic Dark & Light Mode detection matching your active KeyHelp theme.
- Automatic German / English UI labels based on the `html lang` attribute.
- Emits `pluginStatsBoxReady` after rendering for seamless compatibility with [KeyHelp Dashboard Layout Customizer](https://github.com/NicoMSH-plugins/keyhelp-dashboard-layout-customizer).

***

## File Structure

```text
dashboard-layout/
├── plugin_stats.php     # Backend endpoint fetching GitHub release & asset stats
├── download_box.conf    # Repository URLs and widget configuration
└── download_box.js      # Frontend 3-column widget, theme sync & older-releases popup
```

***

## Step-by-Step Installation

### Step 1: Create Directory & Download Files

Create the target directory inside KeyHelp and download the files via `wget` (adjust the repository slug in the URL if you chose a different repo name):

```
mkdir -p /home/keyhelp/www/keyhelp/dashboard-layout
cd /home/keyhelp/www/keyhelp/dashboard-layout

wget -O plugin_stats.php https://github.com/NicoMSH-plugins/keyhelp-github-release-downloads/releases/download/1.0.0/plugin_stats.php
wget -O download_box.conf https://github.com/NicoMSH-plugins/keyhelp-github-release-downloads/releases/download/1.0.0/download_box.conf
wget -O download_box.js https://github.com/NicoMSH-plugins/keyhelp-github-release-downloads/releases/download/1.0.0/download_box.js
```

### Step 2: Configure Your Repositories

Edit `download_box.conf` and add the GitHub repositories you want to track:

```
nano /home/keyhelp/www/keyhelp/dashboard-layout/download_box.conf
```

### Step 3: Set Ownership and Permissions

Set ownership to `keyhelp:keyhelp` and apply standard file/directory permissions:

```
chown -R keyhelp:keyhelp /home/keyhelp/www/keyhelp/dashboard-layout
chmod 755 /home/keyhelp/www/keyhelp/dashboard-layout
chmod 644 /home/keyhelp/www/keyhelp/dashboard-layout/plugin_stats.php /home/keyhelp/www/keyhelp/dashboard-layout/download_box.conf /home/keyhelp/www/keyhelp/dashboard-layout/download_box.js
```

### Step 4: Add the Loader in KeyHelp White-Label JavaScript

Open your KeyHelp admin panel, go to **White-Label -> JavaScript**, and add the following snippet (if you also use `dashboard_layout.js`, load it first):

```
if ('admin_dashboard' === getUrlQueryParameterValue('page')) {
    $(function() {
        const ts = '?_=' + Date.now();
        // $.getScript('dashboard-layout/dashboard_layout.js' + ts); // Load first if installed
        $.getScript('dashboard-layout/download_box.js' + ts);
    });
}
```

Save the White-Label settings.

### Step 5: Test in the KeyHelp Dashboard

1. Open the KeyHelp Dashboard and perform a hard reload (**Ctrl + Shift + R**).
2. Verify that the **GitHub Release Downloads** card displays your configured repositories, latest release assets, and download counts.
3. Click **Older Releases** on any repository with multiple releases to inspect historical download numbers in the floating popup.

***

> [!NOTE]
> Make sure your `download_box.conf` contains at least one valid repository entry; otherwise the widget will display a configuration notice.

## Changelog
<details>
<summary><b>Show / Expand Changelog History</b></summary>

#### v1.0.0 (2026-11-22)
- **Initial Release**: Added the 3-column GitHub Release Downloads dashboard widget (`download_box.js`), PHP stats endpoint (`plugin_stats.php`), and `.conf`-based configuration (`download_box.conf`).
- **Older Releases Popup**: Added a floating popup with release badges and dividers to inspect historical release downloads without expanding column heights.
- **Theme & Locale Support**: Added automatic Dark/Light mode detection, German/English UI labels, and `pluginStatsBoxReady` event integration.
</details>
