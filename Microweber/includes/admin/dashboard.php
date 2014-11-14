<?php event_trigger('mw.admin.dashboard.start'); ?>
<div class="mw-ui-col-container" style="padding-left: 35px;">
  <module type="site_stats/admin" subtype="graph" />
  <module type="site_stats/admin" />
  <div class="quick-lists pull-left">
    <h2>
      <?php _e("Quick Links"); ?>
    </h2>
    <div class="mw-ui-row" id="quick-links-row">
        <div class="mw-ui-col">
        <div class="mw-ui-col-container">
        <div class="mw-ui-navigation">
      <?php event_trigger('mw.admin.dashboard.links'); ?>
      <?php $dash_menu = mw()->ui->admin_dashboard_menu(); ?>
      <?php if(!empty($dash_menu)): ?>
      <?php foreach($dash_menu as $item): ?>
      <?php $view = (isset($item['view']) ? $item['view'] : false);  ?>
      <?php $link = (isset($item['link']) ? $item['link'] : false);  ?>
      <?php if($view == false and $link != false){
		  $btnurl =  $link;
	  } else {
		  $btnurl =  admin_url('view:').$item['view'];
	  } ?>
      <?php $icon = (isset($item['icon_class']) ? $item['icon_class'] : false);  ?>
      <?php $text=  $item['text']; ?>
      <a  href="<?php print $btnurl; ?>"><span class="<?php print $icon; ?>"></span><span><?php print $text; ?></span></a>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
    </div>
    </div>
        <div class="mw-ui-col">
        <div class="mw-ui-col-container">
          <div class="mw-ui-navigation">
                <?php $dash_menu = mw()->ui->admin_dashboard_menu_second(); ?>
                <?php if(!empty($dash_menu)): ?>
                <?php foreach($dash_menu as $item): ?>
                <?php $view = (isset($item['view']) ? $item['view'] : false);  ?>
                <?php $link = (isset($item['link']) ? $item['link'] : false);  ?>
                <?php if($view == false and $link != false){
          		  $btnurl =  $link;
          	  } else {
          		  $btnurl =  admin_url('view:').$item['view'];
          	  } ?>
                <?php $icon = (isset($item['icon_class']) ? $item['icon_class'] : false);  ?>
                <?php $text=$item['text']; ?>
                <a  href="<?php print $btnurl; ?>"><span class="<?php print $icon; ?>"></span><span><?php print $text; ?></span></a>
                <?php endforeach; ?>
                <?php endif; ?>
                <?php event_trigger('mw.admin.dashboard.links2'); ?>
                <?php event_trigger('mw.admin.dashboard.help'); ?>
          </div>
        </div>
        </div>
        <div class="mw-ui-col">
        <div class="mw-ui-col-container">
              <div class="mw-ui-navigation">
                    <?php $dash_menu = mw()->ui->admin_dashboard_menu_third(); ?>
                    <?php if(!empty($dash_menu)): ?>
                    <?php foreach($dash_menu as $item): ?>
                    <?php $view = (isset($item['view']) ? $item['view'] : false);  ?>
                    <?php $link = (isset($item['link']) ? $item['link'] : false);  ?>
                    <?php if($view == false and $link != false){
              		  $btnurl =  $link;
              	  } else {
              		  $btnurl =  admin_url('view:').$item['view'];
              	  } ?>
                    <?php $icon = (isset($item['icon_class']) ? $item['icon_class'] : false);  ?>
                    <?php $text=$item['text']; ?>
                    <a  href="<?php print $btnurl; ?>"><span class="<?php print $icon; ?>"></span><span><?php print $text; ?></span></a>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    <?php event_trigger('mw.admin.dashboard.links3'); ?>
                  </div>
        </div>
        </div>
    </div>


  </div>
  <?php event_trigger('mw.admin.dashboard.main'); ?>
</div>
