var sdm = [];
sdm.datechart = false;
sdm.geochart = false;
sdm.activeTab = sdmAdminStats.activeTab;
sdm.apiKey = sdmAdminStats.apiKey;

jQuery('#gdm_date_buttons button').click(function (e) {
	jQuery('#gdm_choose_date').find('input[name="gdm_stats_start_date"]').val(jQuery(this).attr('data-start-date'));
	jQuery('#gdm_choose_date').find('input[name="gdm_stats_end_date"]').val(jQuery(this).attr('data-end-date'));
});

function gdm_init_chart(tab) {
	if (!sdm.datechart && tab === 'datechart') {
		sdm.datechart = true;
		google.charts.load('current', { 'packages': ['corechart'] });
		google.charts.setOnLoadCallback(gdm_drawDateChart);
	} else if (!sdm.geochart && tab === 'geochart') {
		sdm.geochart = true;
		var chartOpts = {};
		chartOpts.packages = ['geochart'];
		if (sdm.apiKey) {
			chartOpts.mapsApiKey = sdm.apiKey;
		} else {
			//show API Key warning
			jQuery('#gdm-api-key-warning').fadeIn('slow');
		}
		google.charts.load('current', chartOpts);
		google.charts.setOnLoadCallback(gdm_drawGeoChart);
	}
}
function gdm_drawDateChart() {
	var gdm_dateData = new google.visualization.DataTable();
	gdm_dateData.addColumn('string', sdmAdminStats.str.date);
	gdm_dateData.addColumn('number', sdmAdminStats.str.numberOfDownloads);
	gdm_dateData.addRows(sdmAdminStats.dByDate);

	var gdm_dateChart = new google.visualization.AreaChart(document.getElementById('downloads_chart'));
	gdm_dateChart.draw(gdm_dateData, {
		width: 'auto', height: 300, title: sdmAdminStats.str.downloadsByDate, colors: ['#3366CC', '#9AA2B4', '#FFE1C9'],
		hAxis: { title: sdmAdminStats.str.date, titleTextStyle: { color: 'black' } },
		vAxis: { title: sdmAdminStats.str.downloads, titleTextStyle: { color: 'black' } },
		legend: 'top'
	});
}
function gdm_drawGeoChart() {

	var gdm_countryData = google.visualization.arrayToDataTable(sdmAdminStats.dByCountry);

	var gdm_countryOptions = { colorAxis: { colors: ['#ddf', '#00f'] } };

	var gdm_countryChart = new google.visualization.GeoChart(document.getElementById('country_chart'));

	gdm_countryChart.draw(gdm_countryData, gdm_countryOptions);

}
jQuery(function () {
	gdm_init_chart(sdm.activeTab);
	jQuery('div.gdm-tabs a').click(function (e) {
		e.preventDefault();
		var tab = jQuery(this).attr('data-tab-name');
		jQuery('div.gdm-tabs').find('a').removeClass('nav-tab-active');
		jQuery(this).addClass('nav-tab-active');
		jQuery('div.gdm-tabs-content-wrapper').find('div.gdm-tab').hide();
		jQuery('div.gdm-tabs-content-wrapper').find('div[data-tab-name="' + tab + '"]').fadeIn('fast');
		gdm_init_chart(tab);
		jQuery('#gdm_choose_date').find('input[name="gdm_active_tab"]').val(tab);
	});
	jQuery('.datepicker').datepicker({
		dateFormat: 'yy-mm-dd'
	});
});