// NODE_PATH=/path/to/test/node_modules node tests/admin-regression.cjs
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { JSDOM } = require('jsdom');
const dom = new JSDOM(`<!doctype html><html><body>
<div class="plugin-state" data-plugin="woocommerce" data-file="woocommerce/woocommerce.php"><div class="plugin-state_loading"></div><div class="plugin-state_installed" style="display:none"></div><a class="plugin-state_install" style="display:none"></a></div>
<button class="add_global_attribute"></button><table class="global_attribute_table"><tbody class="sortable-list"></tbody></table>
<table class="woo-seo-table"><tbody class="sortable-list"><tr><td><button class="add_new_seo_item"></button></td></tr></tbody></table>
</body></html>`, { runScripts: 'outside-only', url: 'http://localhost/' });
global.window = dom.window;
global.document = dom.window.document;
const jquery = require('jquery');
const $ = jquery.fn ? jquery : jquery(dom.window);
dom.window.jQuery = $;
dom.window.woonuxtData = { ajaxurl: '/wp-admin/admin-ajax.php', nonce: 'fixture-nonce' };
const attack = '\"><img src=x onerror="alert(1)"> & \' test';
dom.window.woonuxtProductAttributes = [{ attribute_name: attack, attribute_label: attack }];
dom.window.prompt = () => attack;
dom.window.confirm = () => true;
dom.window.alert = () => { throw new Error('Unexpected alert'); };
const requests = [];
$.ajax = (options) => { requests.push(options); return $.Deferred().resolve('installed').promise(); };
dom.window.eval(fs.readFileSync(path.join(__dirname, '../assets/admin.js'), 'utf8'));
(async () => {
  await new Promise(resolve => $(resolve));
  assert.equal(requests[0].data.action, 'woonuxt_check_plugin_status');
  assert.equal(requests[0].data.security, 'fixture-nonce');
  assert.notEqual($('.plugin-state_installed').css('display'), 'none');
  assert.equal($('.plugin-state_loading').css('display'), 'none');
  $('.add_global_attribute').trigger('click');
  assert.equal($('.global_attribute_table option').text(), attack);
  assert.equal($('.global_attribute_table option').val(), 'pa_' + attack);
  assert.equal($('.global_attribute_table img').length, 0);
  assert.equal($('.global_attribute_table .sortable-item').attr('draggable'), 'true');
  $('.add_new_seo_item').trigger('click');
  assert.equal($('.seo_item_provider').text(), attack);
  assert.equal($('.woo-seo-table input[type=hidden]').val(), attack);
  assert.equal($('.woo-seo-table img').length, 0);
  assert.match($('.woo-seo-table input[type=hidden]').attr('name'), /^woonuxt_options\[wooNuxtSEO\]\[seo_[a-z0-9]+\]\[provider\]$/);
  $('.remove_seo_item').trigger('click');
  await new Promise(resolve => setTimeout(resolve, 350));
  assert.equal($('.seo_item_provider').length, 0);
  console.log('PASS: dependency AJAX, attribute/social escaping, stable input names, draggable rows, and social deletion');
  dom.window.close();
})().catch(error => { console.error(error); process.exitCode = 1; dom.window.close(); });
