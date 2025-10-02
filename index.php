<?php

$rss_feed_url = 'https://www.vox.com/rss/index.xml';
$fallback_feeds = [
    'https://feeds.bbci.co.uk/news/rss.xml',
    'https://rss.cnn.com/rss/edition.rss',
    'https://feeds.reuters.com/reuters/topNews'
];

function fetchRSSFeed($url) {
    if (!function_exists('curl_init')) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        return $response !== false ? $response : false;
    }
    
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    
    curl_close($ch);
    
    if ($curl_error) {
        return false;
    }
    
    if ($http_code !== 200 || !$response) {
        return false;
    }
    
    return $response;
}

function parseRSSFeed($xml_content) {
    libxml_use_internal_errors(true);
    $rss = simplexml_load_string($xml_content);
    
    if ($rss === false) {
        $errors = libxml_get_errors();
        return false;
    }
    
    return $rss;
}

function formatDate($date_string) {
    $date = new DateTime($date_string);
    return $date->format('F j, Y \a\t g:i A');
}

function truncateText($text, $length = 200) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

function extractImagesFromHTML($html) {
    $image_sources = [];
    
    preg_match_all('/<img[^>]+src="([^"]+)"/i', $html, $matches);
    if (!empty($matches[1])) {
        $image_sources = array_merge($image_sources, $matches[1]);
    }
    
    // Pattern 2: Standard img tags with single quotes
    preg_match_all("/<img[^>]+src='([^']+)'/i", $html, $matches);
    if (!empty($matches[1])) {
        $image_sources = array_merge($image_sources, $matches[1]);
    }
    
    // Pattern 3: Look for data-src (lazy loading)
    preg_match_all('/data-src="([^"]+)"/i', $html, $matches);
    if (!empty($matches[1])) {
        $image_sources = array_merge($image_sources, $matches[1]);
    }
    
    preg_match_all('/background-image:\s*url\(["\']?([^"\']+)["\']?\)/i', $html, $matches);
    if (!empty($matches[1])) {
        $image_sources = array_merge($image_sources, $matches[1]);
    }
    
    return $image_sources;
}

function extractImageFromItem($item) {
    $image_sources = [];
    
    if (isset($item->enclosure['url'])) {
        $image_sources[] = (string)$item->enclosure['url'];
    }
    
    if (isset($item->image)) {
        $image_sources[] = (string)$item->image;
    }
    
    if (isset($item->media->content['url'])) {
        $image_sources[] = (string)$item->media->content['url'];
    }
    
    if (isset($item->media->thumbnail['url'])) {
        $image_sources[] = (string)$item->media->thumbnail['url'];
    }
    
    if (isset($item->description)) {
        $description = (string)$item->description;
        $image_sources = array_merge($image_sources, extractImagesFromHTML($description));
    }
    
    if (isset($item->summary)) {
        $summary = (string)$item->summary;
        $image_sources = array_merge($image_sources, extractImagesFromHTML($summary));
    }
    
    if (isset($item->content)) {
        $content = (string)$item->content;
        $image_sources = array_merge($image_sources, extractImagesFromHTML($content));
    }
    
    $valid_images = [];
    foreach ($image_sources as $source) {
        $source = trim($source);
        if (!empty($source)) {
            if (strpos($source, '//') === 0) {
                $source = 'https:' . $source;
            } elseif (strpos($source, '/') === 0) {
                $source = 'https://www.vox.com' . $source;
            }
            
            if (filter_var($source, FILTER_VALIDATE_URL)) {
                $extension = strtolower(pathinfo(parse_url($source, PHP_URL_PATH), PATHINFO_EXTENSION));
                if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']) || 
                    strpos($source, 'image') !== false ||
                    strpos($source, 'photo') !== false) {
                    $valid_images[] = $source;
                }
            }
        }
    }
    
    return !empty($valid_images) ? $valid_images[0] : null;
}

$rss_data = null;
$error_message = '';
$used_feed_url = $rss_feed_url;

$xml_content = fetchRSSFeed($rss_feed_url);

if ($xml_content) {
    $rss_data = parseRSSFeed($xml_content);
    if (!$rss_data) {
        $error_message = 'Failed to parse RSS feed. The feed may be malformed.';
    }
} else {
    $error_message = 'Failed to fetch Vox RSS feed. Trying alternative feeds...';
    foreach ($fallback_feeds as $fallback_url) {
        $xml_content = fetchRSSFeed($fallback_url);
        if ($xml_content) {
            $rss_data = parseRSSFeed($xml_content);
            if ($rss_data) {
                $used_feed_url = $fallback_url;
                $error_message = '';
                break;
            }
        }
    }
    
    if (!$rss_data) {
        $error_message = 'Failed to fetch any RSS feed. Please check your internet connection and try again.';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vox News RSS Reader</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;line-height:1.6;color:#333;background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);min-height:100vh}

        .container{max-width:100%;margin:0 auto;padding:15px}.header{text-align:center;margin-bottom:40px;color:white}.header h1{font-size:2.5rem;margin-bottom:10px;text-shadow:2px 2px 4px rgba(0,0,0,0.3)}.header p{font-size:1.1rem;opacity:0.9}.feed-info{background:white;border-radius:10px;padding:20px;margin-bottom:30px;box-shadow:0 4px 6px rgba(0,0,0,0.1)}.feed-info h2{color:#2c3e50;margin-bottom:10px;font-size:1.8rem}.feed-info p{color:#7f8c8d;font-size:1rem}.error-message{background:#e74c3c;color:white;padding:20px;border-radius:10px;margin-bottom:30px;text-align:center;font-size:1.1rem}

        .articles-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;max-width:100%}@media (min-width:1200px){.articles-grid{grid-template-columns:repeat(4,1fr);gap:15px}}@media (min-width:1600px){.articles-grid{grid-template-columns:repeat(5,1fr);gap:15px}}

        .article-card{background:white;border-radius:12px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,0.08);transition:transform 0.2s ease,box-shadow 0.2s ease;position:relative;border:1px solid rgba(255,255,255,0.2);will-change:transform}.article-card:hover,.article-card.hovered{transform:translateY(-4px) scale(1.01);box-shadow:0 8px 25px rgba(0,0,0,0.15)}

        .article-image{width:100%;height:180px;object-fit:cover;background:linear-gradient(45deg,#f0f0f0,#e0e0e0);display:flex;align-items:center;justify-content:center;color:#999;font-size:14px;position:relative;overflow:hidden}.article-image img{width:100%;height:100%;object-fit:cover;transition:transform 0.3s ease,opacity 0.3s ease}.article-image img:hover{transform:scale(1.05)}.lazy-image{opacity:0;transition:opacity 0.3s ease}.lazy-image.loaded{opacity:1}.no-image{background:linear-gradient(135deg,#667eea,#764ba2);color:white;font-weight:500}

        .article-content{padding:20px}.article-title{font-size:1.3rem;font-weight:700;margin-bottom:12px;line-height:1.3}.article-title a{color:#2c3e50;text-decoration:none;transition:all 0.3s ease;display:block}.article-title a:hover{color:#3498db;text-shadow:0 2px 4px rgba(52,152,219,0.3)}.article-description{color:#5a6c7d;margin-bottom:18px;line-height:1.6;font-size:0.95rem}

        .article-meta{display:flex;justify-content:space-between;align-items:center;font-size:0.9rem;color:#7f8c8d;border-top:1px solid #f1f3f4;padding-top:18px;margin-top:5px}.article-date{font-style:italic;background:#f8f9fa;padding:4px 8px;border-radius:6px;font-size:0.85rem}.read-more{color:#3498db;text-decoration:none;font-weight:600;transition:all 0.3s ease;padding:6px 12px;border-radius:6px;background:rgba(52,152,219,0.1)}.read-more:hover{color:#2980b9;background:rgba(52,152,219,0.2);transform:translateX(2px)}

        .loading{text-align:center;color:white;font-size:1.2rem;padding:40px}.loading .loading-spinner{margin:0 auto 20px}.refresh-btn{background:linear-gradient(135deg,#3498db,#2980b9);color:white;border:none;padding:15px 30px;border-radius:25px;cursor:pointer;font-size:1rem;font-weight:600;margin:30px auto;display:block;transition:all 0.3s ease;box-shadow:0 4px 15px rgba(52,152,219,0.3)}.refresh-btn:hover{background:linear-gradient(135deg,#2980b9,#1f5f8b);transform:translateY(-2px);box-shadow:0 6px 20px rgba(52,152,219,0.4)}.loading-spinner{display:inline-block;width:20px;height:20px;border:3px solid rgba(255,255,255,.3);border-radius:50%;border-top-color:#fff;animation:spin 1s ease-in-out infinite;margin-right:10px}@keyframes spin{to{transform:rotate(360deg)}}.fade-in{animation:fadeIn 0.6s ease-in}@keyframes fadeIn{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}@media (max-width:768px){.container{padding:10px}.header h1{font-size:2rem}.articles-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📰 3.3.10 Assessment: RSS Feed 🫰🏻</h1>
            <p>Stay updated with the latest news from Vox</p>
        </div>

        <?php if ($error_message): ?>
            <div class="error-message">
                <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
            </div>
            <button class="refresh-btn" onclick="location.reload()">🔄 Try Again</button>
        <?php elseif ($rss_data): ?>
            <div class="feed-info fade-in">
                <h2><?php echo htmlspecialchars($rss_data->channel->title ?? 'RSS Feed'); ?></h2>
                <p><?php echo htmlspecialchars($rss_data->channel->description ?? 'Latest news and updates'); ?></p>
                <?php if ($used_feed_url !== $rss_feed_url): ?>
                    <p><small>📡 Using fallback feed: <?php echo htmlspecialchars($used_feed_url); ?></small></p>
                <?php endif; ?>
            </div>

            <div class="articles-grid fade-in">
                <?php 
                $items = null;
                
                if (isset($rss_data->channel->item)) {
                    $items = $rss_data->channel->item;
                } elseif (isset($rss_data->item)) {
                    $items = $rss_data->item;
                } elseif (isset($rss_data->entry)) {
                    $items = $rss_data->entry;
                }
                
                if ($items && (is_array($items) || $items instanceof Traversable)) {
                    $item_count = 0;
                    $max_items = 20; 
                    foreach ($items as $item):
                        if ($item_count >= $max_items) break;
                        $item_count++; 
                        $image_url = extractImageFromItem($item);
                        $article_link = $item->link ?? $item->link['href'] ?? '#';
                        $article_title = $item->title ?? 'No Title';
                        
                        $content = $item->description ?? $item->summary ?? $item->content ?? 'No description available';
                        $description = strip_tags($content);
                        
                        $date = $item->pubDate ?? $item->published ?? $item->updated ?? 'Date not available';
                ?>
                    <article class="article-card">
                        <div class="article-image">
                            <?php if ($image_url): ?>
                                <img data-src="<?php echo htmlspecialchars($image_url); ?>" 
                                     alt="<?php echo htmlspecialchars($article_title); ?>"
                                     class="lazy-image"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="no-image" style="display: none;">
                                    📰 No Image Available
                                </div>
                            <?php else: ?>
                                <div class="no-image">
                                    📰 No Image Available
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="article-content">
                            <h3 class="article-title">
                                <a href="<?php echo htmlspecialchars($article_link); ?>" target="_blank" rel="noopener">
                                    <?php echo htmlspecialchars($article_title); ?>
                                </a>
                            </h3>
                            
                            <div class="article-description">
                                <?php echo htmlspecialchars(truncateText($description, 120)); ?>
                            </div>
                            
                            <div class="article-meta">
                                <span class="article-date">
                                    📅 <?php echo isset($date) ? formatDate($date) : 'Date not available'; ?>
                                </span>
                                <a href="<?php echo htmlspecialchars($article_link); ?>" target="_blank" rel="noopener" class="read-more">
                                    Read More →
                                </a>
                            </div>
                        </div>
                    </article>
                <?php 
                    endforeach; 
                } else {
                    echo '<div class="error-message">No articles found in the RSS feed.</div>';
                    echo '<div class="error-message">Debug: Items variable type: ' . gettype($items) . '</div>';
                }
                ?>
            </div>

            <button class="refresh-btn" onclick="location.reload()">🔄 Refresh Feed</button>
        <?php else: ?>
            <div class="loading">
                <div class="loading-spinner"></div>
                <p>Loading RSS feed...</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        setTimeout(()=>location.reload(),300000);
        document.addEventListener('DOMContentLoaded',()=>{
            const cards=document.querySelectorAll('.article-card');
            cards.forEach((card,index)=>{card.style.animationDelay=index*0.05+'s';card.classList.add('fade-in')});
            cards.forEach(card=>{card.addEventListener('mouseenter',()=>card.classList.add('hovered'));card.addEventListener('mouseleave',()=>card.classList.remove('hovered'))});
            const refreshBtn=document.querySelector('.refresh-btn');
            if(refreshBtn)refreshBtn.addEventListener('click',function(){this.innerHTML='<div class="loading-spinner"></div> Refreshing...';this.disabled=true});
            const lazyImages=document.querySelectorAll('.lazy-image');
            const imageObserver=new IntersectionObserver((entries,observer)=>{entries.forEach(entry=>{if(entry.isIntersecting){const img=entry.target;img.src=img.dataset.src;img.classList.add('loaded');img.addEventListener('load',function(){this.style.opacity='1'});img.addEventListener('error',function(){this.style.display='none';const fallback=this.nextElementSibling;if(fallback)fallback.style.display='flex'});observer.unobserve(img)}})},{rootMargin:'50px 0px',threshold:0.01});
            lazyImages.forEach(img=>imageObserver.observe(img));
        });
    </script>
</body>
</html>
