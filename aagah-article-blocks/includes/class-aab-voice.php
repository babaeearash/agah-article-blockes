<?php
if (!defined('ABSPATH')) { exit; }

class AAB_Voice
{
    public static function init()
    {
        add_filter('aab_meta_box_tabs', [__CLASS__, 'register_tab']);
        add_action('aab_article_blocks_saved', [__CLASS__, 'save_from_request']);
        add_shortcode('aagah_voice', [__CLASS__, 'shortcode']);
        add_filter('the_content', [__CLASS__, 'filter_content_audio'], 12);
        add_action('admin_enqueue_scripts', static function () { wp_enqueue_media(); });
    }

    private static function mime_from_url($url)
    {
        $ext = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        $types = ['mp3'=>'audio/mpeg','m4a'=>'audio/mp4','aac'=>'audio/aac','ogg'=>'audio/ogg','oga'=>'audio/ogg','wav'=>'audio/wav','opus'=>'audio/opus'];
        return $types[$ext] ?? '';
    }

    public static function sanitize_items($items)
    {
        if (!is_array($items)) return [];
        $out = [];
        foreach (array_slice($items, 0, 20) as $item) {
            if (is_string($item)) $item = ['url'=>$item];
            if (!is_array($item)) continue;
            $sources = [];
            $url = esc_url_raw(wp_unslash($item['url'] ?? ''));
            if (!$url) continue;
            $primaryMime = sanitize_mime_type($item['mime'] ?? self::mime_from_url($url));
            $normalizedSources = [['url'=>$url, 'mime'=>$primaryMime]];
            foreach ((array)($item['sources'] ?? []) as $source) {
                $sourceUrl = esc_url_raw(wp_unslash($source['url'] ?? ''));
                if ($sourceUrl && $sourceUrl !== $url) $normalizedSources[] = ['url'=>$sourceUrl, 'mime'=>sanitize_mime_type($source['mime'] ?? self::mime_from_url($sourceUrl))];
            }
            $out[] = ['url'=>$url,'title'=>sanitize_text_field(wp_unslash($item['title'] ?? 'فایل صوتی مقاله')),'mime'=>$primaryMime,'sources'=>$normalizedSources];
        }
        return $out;
    }

    public static function get_voices($post_id = null)
    {
        $post_id = $post_id ?: get_the_ID();
        $items = get_post_meta($post_id, AAB_Meta_Keys::VOICE_ITEMS, true);
        if (!is_array($items) || !$items) {
            $legacy = (string)get_post_meta($post_id, AAB_Meta_Keys::VOICE, true);
            $items = $legacy ? [['url'=>$legacy,'title'=>'فایل صوتی مقاله']] : [];
        }
        return self::sanitize_items($items);
    }

    public static function get_voice_url($post_id = null)
    {
        $voices = self::get_voices($post_id);
        return $voices ? $voices[0]['url'] : '';
    }

    public static function register_tab($tabs)
    {
        $tabs['voice'] = ['label'=>'صدا','icon'=>'controls-volumeon','render'=>[__CLASS__,'render_panel'],'count'=>static function($post_id){return count(self::get_voices($post_id));}];
        return $tabs;
    }

    public static function render_panel($post)
    {
        $items = self::get_voices($post->ID);
        AAB_Meta_Box::panel_head('فایل‌های صوتی نوشته','چند فایل صوتی اضافه کنید؛ URL، کتابخانه رسانه یا کد audio/source پذیرفته می‌شود.','[aagah_voice]');
        ?>
        <div class="aab-voice-admin" data-aab-voice-admin>
            <textarea class="widefat" name="aab_voice_html" rows="3" placeholder="کد audio/source (اختیاری)" dir="ltr"></textarea>
            <div data-aab-voice-items>
            <?php foreach ($items as $i=>$item) : ?><div class="aab-voice-admin-row" draggable="true">
                <input type="url" name="aab_voice_items[<?php echo esc_attr($i); ?>][url]" value="<?php echo esc_url($item['url']); ?>" placeholder="https://…" dir="ltr" class="widefat">
                <input type="text" name="aab_voice_items[<?php echo esc_attr($i); ?>][title]" value="<?php echo esc_attr($item['title']); ?>" placeholder="عنوان صوت" class="widefat">
                <?php foreach (array_slice((array)($item['sources'] ?? []), 1, null, true) as $sourceIndex => $source) : ?><input type="hidden" data-aab-alt-source name="aab_voice_items[<?php echo esc_attr($i); ?>][sources][<?php echo esc_attr($sourceIndex); ?>][url]" value="<?php echo esc_url($source['url']); ?>"><input type="hidden" data-aab-alt-source name="aab_voice_items[<?php echo esc_attr($i); ?>][sources][<?php echo esc_attr($sourceIndex); ?>][mime]" value="<?php echo esc_attr($source['mime']); ?>"><?php endforeach; ?>
                <button type="button" class="button" data-aab-voice-pick>کتابخانه</button><button type="button" class="button" data-aab-voice-up aria-label="انتقال به بالا">↑</button><button type="button" class="button" data-aab-voice-down aria-label="انتقال به پایین">↓</button><button type="button" class="button" data-aab-voice-remove aria-label="حذف">×</button>
            </div><?php endforeach; ?>
            </div>
            <button type="button" class="button" data-aab-voice-add>افزودن فایل صوتی</button>
            <p class="description">فایل‌ها را با دکمه‌ها یا کشیدن مرتب کنید. کد HTML فقط از audio و source خوانده می‌شود.</p>
        </div>
        <script>
        (function(){const root=document.currentScript.previousElementSibling,list=root.querySelector('[data-aab-voice-items]');
        const reindex=()=>list.querySelectorAll('.aab-voice-admin-row').forEach((row,i)=>{row.querySelector('input[type=url]:not([data-aab-alt-source])').name='aab_voice_items['+i+'][url]';row.querySelector('input[type=text]').name='aab_voice_items['+i+'][title]';row.querySelectorAll('[data-aab-alt-source]').forEach(x=>x.name=x.name.replace(/aab_voice_items\[\d+\]/,'aab_voice_items['+i+']'));});
        const add=(url='',title='')=>{const row=document.createElement('div');row.className='aab-voice-admin-row';row.draggable=true;row.innerHTML='<input type="url" class="widefat" dir="ltr"><input type="text" class="widefat"><button type="button" class="button" data-aab-voice-pick>کتابخانه</button><button type="button" class="button" data-aab-voice-up>↑</button><button type="button" class="button" data-aab-voice-down>↓</button><button type="button" class="button" data-aab-voice-remove>×</button>';row.querySelector('input[type=url]').value=url;row.querySelector('input[type=text]').value=title;list.appendChild(row);reindex();};
        root.addEventListener('click',e=>{if(e.target.closest('[data-aab-voice-add]'))add();const up=e.target.closest('[data-aab-voice-up]'),down=e.target.closest('[data-aab-voice-down]'),rm=e.target.closest('[data-aab-voice-remove]'),pick=e.target.closest('[data-aab-voice-pick]');if(up){const r=up.closest('.aab-voice-admin-row');if(r.previousElementSibling)list.insertBefore(r,r.previousElementSibling);reindex();}if(down){const r=down.closest('.aab-voice-admin-row');if(r.nextElementSibling)list.insertBefore(r.nextElementSibling,r);reindex();}if(rm){rm.closest('.aab-voice-admin-row').remove();reindex();}if(pick&&window.wp&&wp.media){const row=pick.closest('.aab-voice-admin-row'),frame=wp.media({title:'انتخاب فایل‌های صوتی',library:{type:'audio'},multiple:true});frame.on('select',()=>frame.state().get('selection').toJSON().forEach((att,i)=>{if(!i){row.querySelector('input[type=url]').value=att.url;if(!row.querySelector('input[type=text]').value)row.querySelector('input[type=text]').value=att.title||'';}else add(att.url,att.title||'فایل صوتی مقاله');}));frame.open();}});
        let drag;root.addEventListener('dragstart',e=>drag=e.target.closest('.aab-voice-admin-row'));root.addEventListener('dragover',e=>{const row=e.target.closest('.aab-voice-admin-row');if(drag&&row&&drag!==row){e.preventDefault();list.insertBefore(drag,row);}});root.addEventListener('drop',reindex);reindex();})();
        </script>
        <?php
    }

    public static function save_from_request($post_id)
    {
        $items = isset($_POST['aab_voice_items']) ? self::sanitize_items(wp_unslash($_POST['aab_voice_items'])) : [];
        if (!empty($_POST['aab_voice_html'])) {
            $html = wp_unslash($_POST['aab_voice_html']);
            if (preg_match_all('/<audio\b[^>]*>.*?<\/audio>/is',$html,$groups)) {
                foreach ($groups[0] as $group) {
                    $sources=[]; if(preg_match_all('/<source\b[^>]*src=["\']([^"\']+)["\'][^>]*>/i',$group,$matches,PREG_SET_ORDER)) foreach($matches as $m){preg_match('/type=["\']([^"\']+)/i',$m[0],$type);$sources[]=['url'=>$m[1],'mime'=>$type[1]??self::mime_from_url($m[1])];}
                    elseif(preg_match('/<audio\b[^>]*src=["\']([^"\']+)["\']/i',$group,$m))$sources[]=['url'=>$m[1],'mime'=>self::mime_from_url($m[1])];
                    if($sources)$items[]=['url'=>$sources[0]['url'],'title'=>'فایل صوتی مقاله','sources'=>$sources];
                }
            } elseif(preg_match_all('/<source\b[^>]*src=["\']([^"\']+)["\'][^>]*>/i',$html,$matches)) {
                foreach($matches[1] as $url)$items[]=['url'=>$url,'title'=>'فایل صوتی مقاله'];
            }
        }
        $items=self::sanitize_items($items);
        if($items){update_post_meta($post_id,AAB_Meta_Keys::VOICE_ITEMS,$items);update_post_meta($post_id,AAB_Meta_Keys::VOICE,$items[0]['url']);}
        else{delete_post_meta($post_id,AAB_Meta_Keys::VOICE_ITEMS);delete_post_meta($post_id,AAB_Meta_Keys::VOICE);}
    }

    public static function shortcode($atts=[])
    {
        $atts=shortcode_atts(['post_id'=>0,'id'=>''],$atts,'aagah_voice');$post_id=absint($atts['post_id'])?:get_the_ID();$voices=self::get_voices($post_id);
        if($atts['id']!==''){if(ctype_digit((string)$atts['id'])){$i=(int)$atts['id'];$voices=isset($voices[$i])?[$voices[$i]]:[];}else{$voices=array_values(array_filter($voices,static function($v)use($atts){return sanitize_title($v['title'])===sanitize_title($atts['id']);}));}}
        return self::render_players($voices,$post_id);
    }

    private static function schema_html($voices,$post_id)
    {
        static $emitted=[];$graph=[];
        foreach($voices as $v){$sources=$v['sources']??[['url'=>$v['url'],'mime'=>$v['mime']??'']];foreach($sources as $source){$url=$source['url']??'';if(!$url||isset($emitted[$url]))continue;$emitted[$url]=true;$node=['@type'=>'AudioObject','name'=>$v['title']?:get_the_title($post_id),'contentUrl'=>$url,'isPartOf'=>['@id'=>get_permalink($post_id).'#article']];$mime=$source['mime']??($v['mime']?:self::mime_from_url($url));if($mime)$node['encodingFormat']=$mime;$attachment=attachment_url_to_postid($url);if($attachment){$metadata=wp_get_attachment_metadata($attachment);$seconds=(int)($metadata['length']??0);if($seconds>0)$node['duration']='PT'.$seconds.'S';}$graph[]=$node;}}
        return $graph?'<script type="application/ld+json">'.wp_json_encode(['@context'=>'https://schema.org','@graph'=>$graph],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP).'</script>':'';
    }

    public static function render_players($voices,$post_id)
    {
        if(!$voices)return '';AAB_Assets::enqueue_frontend();$html='<div class="aab-voice-list">';
        foreach($voices as $voice){$sources=$voice['sources']??[['url'=>$voice['url'],'mime'=>$voice['mime']??self::mime_from_url($voice['url'])]];$html.='<section class="aab-voice-player" data-aab-player data-title="'.esc_attr($voice['title']).'" data-artist="'.esc_attr(get_the_title($post_id)).'" data-artwork="'.esc_url(get_the_post_thumbnail_url($post_id,'thumbnail')?:'').'"><header class="aab-voice-head"><strong class="aab-voice-title">'.esc_html($voice['title']).'</strong></header><audio controls preload="metadata" class="aab-voice-audio">';foreach($sources as $source)$html.='<source src="'.esc_url($source['url']).'"'.(!empty($source['mime'])?' type="'.esc_attr($source['mime']).'"':'').'>';
        $html.='مرورگر شما از پخش صدا پشتیبانی نمی‌کند.</audio><div class="aab-audio-controls"><button type="button" data-aab-play aria-label="پخش">▶</button><input type="range" min="0" max="1000" value="0" data-aab-seek aria-label="موقعیت پخش"><span data-aab-remaining>−۰:۰۰</span></div><span class="aab-audio-status" data-aab-audio-status role="status" aria-live="polite"></span></section>';}
        return $html.'</div>'.self::schema_html($voices,$post_id);
    }

    public static function filter_content_audio($content)
    {
        if(is_admin())return $content;$context=class_exists('AAB_Content_Blocks')?AAB_Content_Blocks::get_post_context():0;if(!$context&&!is_singular('post'))return $content;$post_id=$context?:get_the_ID();
        return preg_replace_callback('/<audio\b[^>]*>.*?<\/audio>/is',static function($m)use($post_id){$audio=$m[0];if(strpos($audio,'aab-voice-audio')!==false)return $audio;$sources=[];if(preg_match_all('/<source\b[^>]*src=["\']([^"\']+)["\'][^>]*>/i',$audio,$matches,PREG_SET_ORDER))foreach($matches as $source){preg_match('/type=["\']([^"\']+)/i',$source[0],$type);$sources[]=['url'=>$source[1],'mime'=>$type[1]??self::mime_from_url($source[1])];}elseif(preg_match('/<audio\b[^>]*src=["\']([^"\']+)["\']/i',$audio,$m))$sources[]=['url'=>$m[1],'mime'=>self::mime_from_url($m[1])];if(!$sources)return $audio;$voice=['url'=>$sources[0]['url'],'title'=>'فایل صوتی مقاله','mime'=>$sources[0]['mime'],'sources'=>$sources];return self::render_players([$voice],$post_id);},$content);
    }
}
