#!/usr/bin/env bash
# راه‌اندازی وردپرس لوکال (WP Playground, بدون MySQL) با این قالب برای تست دستی/خودکار.
# نیازمندی: node 18+. استفاده: bash bin/dev-playground.sh  →  http://127.0.0.1:9400
set -e
THEME_DIR="$(cd "$(dirname "$0")/.." && pwd)"
WORK="${PLAYGROUND_DIR:-/tmp/wp}"; TOOLS="${TOOLS_DIR:-$HOME/.cache/tools}"
mkdir -p "$WORK" "$TOOLS"
cd "$TOOLS"; [ -d node_modules/@wp-playground/cli ] || npm i --silent @wp-playground/cli
if [ ! -f "$WORK/wp.zip" ]; then curl -sL -o "$WORK/wp.zip" https://codeload.github.com/WordPress/WordPress/zip/refs/tags/6.7.1; fi
if [ ! -d "$WORK/site" ]; then (cd "$WORK" && python3 -c "import zipfile;zipfile.ZipFile('wp.zip').extractall('.')" && mv WordPress-6.7.1 site); fi
rm -rf "$WORK/site/wp-content/themes/evented-edu"; cp -r "$THEME_DIR" "$WORK/site/wp-content/themes/evented-edu"
cat > "$WORK/bp.json" <<'JSON'
{"login":true,"steps":[
{"step":"defineWpConfigConsts","consts":{"WP_DEBUG":true,"WP_DEBUG_LOG":"/wordpress/debug.log","WP_DEBUG_DISPLAY":false}},
{"step":"activateTheme","themeFolderName":"evented-edu"},
{"step":"runPHP","code":"<?php require '/wordpress/wp-load.php'; $c=wp_insert_term('سواد رسانه','category'); for($i=1;$i<=6;$i++){ $id=wp_insert_post(['post_title'=>'مقالهٔ آزمایشی '.$i,'post_name'=>'post-'.$i,'post_content'=>str_repeat('متن آزمایشی برای بررسی صفحه. ',40),'post_status'=>'publish']); if(!is_wp_error($c)) wp_set_post_terms($id,[$c['term_id']],'category'); } foreach(['panel','login','courses'] as $s){ wp_insert_post(['post_title'=>$s,'post_name'=>$s,'post_type'=>'page','post_status'=>'publish']); } global $wp_rewrite; $wp_rewrite->set_permalink_structure('/%postname%/'); $wp_rewrite->flush_rules(true); echo 'seeded';"}
]}
JSON
cat > "$TOOLS/runpg.mjs" <<'JS'
const of=globalThis.fetch;const fs=await import('fs');
globalThis.fetch=async(...a)=>{const u=String(a[0]&&a[0].url||a[0]);
 if(u.includes('api.wordpress.org/core/version-check')) return new Response(JSON.stringify({offers:[{response:'upgrade',version:'6.7.1',download:'https://wordpress.org/wordpress-6.7.1.zip'}]}),{headers:{'content-type':'application/json'}});
 if(u.includes('stable-check')) return new Response(JSON.stringify({"6.7.1":"latest"}),{headers:{'content-type':'application/json'}});
 if(u.includes('wordpress.org/wordpress-')&&u.endsWith('.zip')){const b=fs.readFileSync(process.env.WORK+'/wp.zip');return new Response(b,{headers:{'content-type':'application/zip','content-length':String(b.length)}})}
 return of(...a)};
process.argv=['node','cli','server','--wp=6.7.1','--mount-before-install='+process.env.WORK+'/site:/wordpress','--blueprint='+process.env.WORK+'/bp.json','--port=9400'];
import('./node_modules/@wp-playground/cli/cli.js');
JS
echo "starting…"; WORK="$WORK" nohup node "$TOOLS/runpg.mjs" > "$WORK/pg.log" 2>&1 &
for i in $(seq 1 90); do sleep 2; grep -q "Ready" "$WORK/pg.log" && break; done; tail -2 "$WORK/pg.log"
