# 📰 3.3.10 Assessment: RSS Feed Parser

A professional web application that parses and displays the latest news from Vox's RSS feed (https://www.vox.com/rss/index.xml).

## ✨ Features

- ✅ **RSS/Atom Feed Parsing** - Handles both RSS and Atom feed formats
- ✅ **Image Support** - Extracts and displays article images with lazy loading
- ✅ **Responsive Design** - Beautiful grid layout that adapts to all screen sizes
- ✅ **Error Handling** - Graceful fallbacks for network issues and malformed feeds
- ✅ **Performance Optimized** - Lazy loading, minified code, and efficient rendering
- ✅ **Modern UI** - Clean design with smooth animations and hover effects
- ✅ **Auto-refresh** - Automatically refreshes every 5 minutes
- ✅ **Fallback Feeds** - Uses alternative RSS feeds if Vox is unavailable

## 📁 Files Included

1. **`index.php`** - Complete RSS parser with all functionality
2. **`rss_reader.html`** - HTML documentation version
3. **`README.md`** - This documentation file

## 🚀 How to Use

### Local Development (XAMPP)
1. Place files in your XAMPP `htdocs` directory
2. Start XAMPP and ensure Apache is running
3. Navigate to `http://localhost/sha2/index.php`
4. RSS feed will be automatically fetched and displayed

### Canvas Submission
1. Upload the `index.php` file to Canvas
2. File contains all necessary code to parse and display RSS feeds

### GitHub Submission
1. Create a new GitHub repository
2. Upload the `index.php` file to the repository
3. Submit the GitHub repository URL to Canvas

## 🔧 Technical Implementation

### RSS Parsing Process
1. **Fetch RSS Feed** - Uses cURL to fetch the RSS feed from Vox
2. **Parse XML** - Uses PHP's SimpleXML extension to parse XML content
3. **Extract Images** - Advanced image extraction from multiple sources
4. **Display Content** - Renders articles in responsive grid layout
5. **Error Handling** - Graceful fallbacks for network issues

### Key Features
- **Multi-format Support** - Handles RSS and Atom feeds
- **Image Extraction** - Finds images in descriptions, media tags, and enclosures
- **Lazy Loading** - Images load only when visible for better performance
- **Responsive Grid** - Automatically adjusts columns based on screen size
- **Fallback Feeds** - Uses BBC, CNN, or Reuters if Vox is unavailable

### Security & Performance
- **XSS Protection** - Uses `htmlspecialchars()` for all output
- **Error Handling** - Validates HTTP responses and XML parsing
- **Optimized Code** - Minified CSS/JS for faster loading
- **Limited Articles** - Shows max 20 articles for better performance

## 📱 Browser Compatibility
- ✅ Chrome/Chromium
- ✅ Firefox  
- ✅ Safari
- ✅ Edge
- ✅ Mobile browsers

## ⚙️ Requirements
- PHP 7.4 or higher
- cURL extension enabled
- SimpleXML extension enabled
- Internet connection for fetching RSS feed

## 🎯 Assignment Requirements Met
- ✅ **Parses RSS feed** from https://www.vox.com/rss/index.xml
- ✅ **Displays articles** in readable format with images
- ✅ **Professional appearance** with modern UI
- ✅ **Error handling** for network issues
- ✅ **Responsive design** for all devices

## 📝 Submission Instructions
1. **Canvas Upload**: Upload `index.php` file directly
2. **GitHub Submission**: Upload to GitHub repository and submit URL
3. **Files to include**: `index.php`, `README.md`, `rss_reader.html`

## 🏆 Project Highlights
- **Advanced RSS parsing** with Atom feed support
- **Image extraction** from multiple sources
- **Performance optimized** with lazy loading
- **Professional UI** with smooth animations
- **Error resilient** with fallback feeds
- **Mobile responsive** design

---
**Created for 3.3.10 Assessment - RSS Feed Parser** 📰
