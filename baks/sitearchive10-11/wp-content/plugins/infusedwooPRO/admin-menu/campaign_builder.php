<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<h1>Automation via Campaign Builder</h1>
<hr>

<p>Since version 1.2.4 of InfusedWoo, you can setup products to trigger a campaign goal inside infusionsoft. The API goal in infusionsoft is used to achieve this. Follow the steps below to set this up on one of your products.</p>
<p>If you want to trigger a campaign goal for <strong>any</strong> woocommerce purchase, follow only steps 2, 3 and 4 below.</p>
<h2 id="steps">Steps</h2>
<h4 id="1-add--update-sku-of-your-product">1. Add / Update SKU of your product</h4>
<p>First, make sure that your product in woocommerce has an SKU value and that the SKU only have <strong>alphanumeric</strong> characters.</p>
<p>You can update the products SKU in the product data area when editing the product, under the &quot;General&quot; tab, see screenshot below:</p>
<p><img style="width: 100%" src="https://mjtokyo.s3-ap-northeast-1.amazonaws.com/cpn1/cpn1.png" alt="Campaign Step 1"></p>
<h4 id="2-add-a-campaign-goal-in-infusionsoft">2. Add a Campaign Goal in Infusionsoft</h4>
<p>In infusionsoft, in the campaign builder, look into the panel on the right side and expand the Goals tab.
Choose the &quot;Other&quot; goal as seen in the screenshot below. Drag this into the campaign space.</p>
<p><img style="width: 100%" src="https://mjtokyo.s3-ap-northeast-1.amazonaws.com/cpn2/cpn2.png" alt="Campaign Step 2"></p>
<h4 id="3-set-goal-as-api-goal">3. Set Goal as API Goal</h4>
<p>Click the pencil icon next to the &quot;Other&quot; goal and configure it as an API call as shown. Hit &quot;Save&quot;.</p>
<p><img style="width: 100%" src="https://mjtokyo.s3-ap-northeast-1.amazonaws.com/cpn3/cpn3.png" alt="Campaign Step 3"></p>
<h4 id="4-configure-the-api-goal">4. Configure the API Goal</h4>
<p>Double-click the campaign API goal to configure. Make the integration name as <strong>&quot;woopurchase&quot;</strong> and in the call name, enter the SKU value of your product.</p>
<p><strong>Note</strong>: If you want to trigger this campaign goal for any woocommerce purchase, you just need to set the call name to &quot;<strong>any</strong>&quot; and it should be triggered whenever a woocommerce purchase is made.</p>
<p>Hit save, and you have successfully configured the campaign goal for your woocommerce product purchase.</p>
<p><img style="width: 100%" src="https://mjtokyo.s3-ap-northeast-1.amazonaws.com/cpn4/cpn4.png" alt="Campaign Step 4"></p>
<p>You can add necessary actions and sequences here to be tied up to this goal. Once your finished editing the campaign, don&#39;t forget to hit the publish button if you want to make the campaign active.</p>
<p>Once your campaign is active and running, the campaign goal should be triggered when the product is purchased.</p>