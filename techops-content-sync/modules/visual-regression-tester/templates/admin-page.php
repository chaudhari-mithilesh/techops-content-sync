<?php
if (!defined('ABSPATH')) {
	exit;
}

$options = get_option('techops_visual_testing_settings');
?>

<div class="wrap">
	<h1>Visual Regression Testing</h1>

	<h2 class="nav-tab-wrapper">
		<a href="#" class="nav-tab nav-tab-active" id="single-test-tab">Single Test</a>
		<a href="#" class="nav-tab" id="site-wide-audit-tab">Site-wide Audit</a>
	</h2>

	<div id="single-test-panel" class="tab-panel">
		<div class="card">
			<h2>Single Test</h2>
			<form id="single-test-form">
				<?php wp_nonce_field('techops_visual_testing', 'techops_visual_testing_nonce'); ?>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="reference_url">Reference URL</label></th>
						<td><input type="url" id="reference_url" name="reference_url" class="regular-text" value="" placeholder="e.g., https://reference.example.com"></td>
					</tr>
					<tr>
						<th scope="row"><label for="test_url">Test URL</label></th>
						<td><input type="url" id="test_url" name="test_url" class="regular-text" value="" placeholder="e.g., https://test.example.com"></td>
					</tr>
				</table>
				<p>
					<button type="button" class="button" id="test-connection">Test Connection</button>
					<button type="submit" class="button button-primary" id="run-single-test">Run Test</button>
				</p>
				<p id="test-connection-result" style="margin-top:10px;"></p>
			</form>
		</div>
	</div>

	<div id="site-wide-audit-panel" class="tab-panel" style="display: none;">
		<div class="card">
			<h2>Site-wide Audit</h2>
			<form id="site-wide-audit-form">
				<?php wp_nonce_field('techops_visual_testing', 'techops_visual_testing_nonce'); ?>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="reference_sitemap_url">Reference Sitemap URL</label></th>
						<td><input type="url" id="reference_sitemap_url" name="reference_sitemap_url" class="regular-text" value="" placeholder="e.g., https://reference.example.com/sitemap.xml"></td>
					</tr>
					<tr>
						<th scope="row"><label for="test_sitemap_url">Test Sitemap URL</label></th>
						<td><input type="url" id="test_sitemap_url" name="test_sitemap_url" class="regular-text" value="" placeholder="e.g., https://test.example.com/sitemap.xml"></td>
					</tr>
				</table>
				<p>
					<button type="submit" class="button button-primary" id="run-site-wide-audit">Run Audit</button>
				</p>
			</form>
			<div id="site-wide-audit-result" style="margin-top:10px;"></div>
		</div>
	</div>

	<div class="card">
		<h2>Recent Tests</h2>
		<table class="wp-list-table widefat fixed striped" id="recent-tests-table">
			<thead>
				<tr>
					<th>Status</th>
					<th>Report</th>
				</tr>
			</thead>
			<tbody>
				<tr><td colspan="2">Loading...</td></tr>
			</tbody>
		</table>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	// Tab functionality
	$('.nav-tab-wrapper a').on('click', function(e) {
		e.preventDefault();
		$('.nav-tab').removeClass('nav-tab-active');
		$(this).addClass('nav-tab-active');
		$('.tab-panel').hide();
		$('#' + $(this).attr('id').replace('-tab', '-panel')).show();
	});

	// Initial display
	$('#single-test-tab').click();

	// Single Test form submission (custom API logic)
	$('#single-test-form').on('submit', function(e) {
		e.preventDefault();
		console.log('[Run Test] Form submit triggered');
		const $form = $(this);
		const $button = $('#run-single-test');
		let $result = $('#single-test-result');
		if ($result.length === 0) {
			$result = $('<div id="single-test-result" style="margin-top:10px;"></div>').insertAfter($form);
		}
		$button.prop('disabled', true);
		console.log('[Run Test] Button disabled');
		// Get form data
		const refUrl = $('#reference_url').val();
		const testUrl = $('#test_url').val();
		console.log('[Run Test] Reference URL:', refUrl);
		console.log('[Run Test] Test URL:', testUrl);
		const reqBody = {
			refUrl: refUrl,
			testUrl: testUrl,
			viewport: {
				width: 1280,
				height: 2041
			}
		};
		console.log('[Run Test] Request body:', reqBody);
		// Make API POST request
		fetch('http://localhost:3000/api/compare', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify(reqBody)
		})
		.then(response => {
			console.log('[Run Test] Raw response:', response);
			return response.json();
		})
		.then(data => {
			console.log('[Run Test] API Response:', data);
			const base = 'http://localhost:3000';
			if (typeof data.diffPercentage !== 'undefined') {
				// Visual diff result
				const refImgUrl = base + data.refImageUrl;
				const testImgUrl = base + data.testImageUrl;
				const diffImgUrl = base + data.diffImageUrl;
				$result.html(
					'<b>Diff:</b> ' + data.diffPercentage + '%<br>' +
					'<b>Reference:</b> <a href="' + refImgUrl + '" target="_blank">' + refImgUrl + '</a><br>' +
					'<b>Test:</b> <a href="' + testImgUrl + '" target="_blank">' + testImgUrl + '</a><br>' +
					'<b>Diff Image:</b> <a href="' + diffImgUrl + '" target="_blank">' + diffImgUrl + '</a>'
				).css({background:'#f8f9fa',padding:'10px',border:'1px solid #ccc','border-radius':'6px'});
				console.log('[Run Test] Result displayed in UI');
			} else if (typeof data.reportUrl !== 'undefined') {
				// Report-only result
				const reportUrl = base + data.reportUrl;
				$result.html(
					'<b>Visual Test Report:</b> <a href="' + reportUrl + '" target="_blank">' + reportUrl + '</a><br>' +
					(typeof data.passed !== 'undefined' ? ('<b>Passed:</b> ' + (data.passed ? 'Yes' : 'No')) : '')
				).css({background:'#f8f9fa',padding:'10px',border:'1px solid #ccc','border-radius':'6px'});
				console.log('[Run Test] Report URL displayed in UI');
			} else {
				$result.html('<span style="color:red;">Unexpected API response.</span>');
				console.warn('[Run Test] Unexpected API response:', data);
			}
		})
		.catch(error => {
			console.error('[Run Test] API Error:', error);
			$result.html('<span style="color:red;">API request failed.</span>');
		})
		.finally(() => {
			$button.prop('disabled', false);
			console.log('[Run Test] Button re-enabled');
		});
	});

	// Test Connection (Single Test)
	$('#test-connection').on('click', function(e) {
		e.preventDefault();
		const referenceUrl = $('#reference_url').val();
		const testUrl = $('#test_url').val();
		const nonce = $('#single-test-form input[name="techops_visual_testing_nonce"]').val();
		const $btn = $(this);
		const $resultPara = $('#test-connection-result');
		if ($resultPara.length === 0) {
			$btn.parent().append('<p id="test-connection-result" style="margin-top:10px;"></p>');
		}
		$btn.prop('disabled', true).text('Testing...');
		$('#test-connection-result').removeClass('test-success test-fail').css('color', '').text('Testing connection...');
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'techops_test_connection',
				reference_url: referenceUrl,
				test_url: testUrl,
				nonce: nonce
			},
			success: function(response) {
				if (response.success) {
					$('#test-connection-result').text('Test Connection Successful: Both URLs are accessible.').css({color: '#155724', background: '#d4edda', padding: '8px', 'border-radius': '4px', 'margin-top': '10px'}).addClass('test-success');
				} else {
					$('#test-connection-result').text('Test Connection Failed: ' + (response.data && response.data.message ? response.data.message : 'Unknown error')).css({color: '#721c24', background: '#f8d7da', padding: '8px', 'border-radius': '4px', 'margin-top': '10px'}).addClass('test-fail');
				}
			},
			error: function() {
				$('#test-connection-result').text('An error occurred while testing the connection.').css({color: '#721c24', background: '#f8d7da', padding: '8px', 'border-radius': '4px', 'margin-top': '10px'}).addClass('test-fail');
			},
			complete: function() {
				$btn.prop('disabled', false).text('Test Connection');
			}
		});
	});

	// Fetch and display recent tests from external API
	function loadRecentTests() {
		const $tbody = $('#recent-tests-table tbody');
		fetch('http://localhost:3000/api/tests/all')
			.then(res => res.json())
			.then(data => {
				if (!Array.isArray(data) || data.length === 0) {
					$tbody.html('<tr><td colspan="2">No recent tests found.</td></tr>');
					return;
				}
				// Show only the latest 10
				const latest = data.slice(0, 10);
				$tbody.empty();
				latest.forEach(test => {
					const status = test.passed ? '<span style="color:green;font-weight:bold;">Passed</span>' : '<span style="color:red;font-weight:bold;">Failed</span>';
					const reportUrl = 'http://localhost:3000' + test.reportUrl;
					$tbody.append('<tr>' +
						'<td>' + status + '</td>' +
						'<td><a href="' + reportUrl + '" target="_blank">View Report</a></td>' +
					'</tr>');
				});
			})
			.catch(err => {
				$tbody.html('<tr><td colspan="2" style="color:red;">Failed to load recent tests.</td></tr>');
				console.error('[Recent Tests] API error:', err);
			});
	}
	loadRecentTests();

	// Site-wide Audit form submission (AJAX, Run Test style)
	$('#site-wide-audit-form').on('submit', function(e) {
		console.log('[Site-wide Audit] Form submit triggered');
		e.preventDefault();
		const $form = $(this);
		const $button = $('#run-site-wide-audit');
		let $result = $('#site-wide-audit-result');
		$button.prop('disabled', true);
		$result.html('<span>Running site-wide audit...</span>').css({background:'#f8f9fa',padding:'10px',border:'1px solid #ccc','border-radius':'6px'});
		const refSitemapUrl = $('#reference_sitemap_url').val();
		const testSitemapUrl = $('#test_sitemap_url').val();
		const reqBody = {
			testsitemapurl: testSitemapUrl,
			refsitemapurl: refSitemapUrl
		};
		console.log('[Site-wide Audit] Request body:', reqBody);
		console.log('[Site-wide Audit] Fetching:', 'http://localhost:3000/api/test/sitemap');
		fetch('http://localhost:3000/api/test/sitemap', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify(reqBody)
		})
		.then(response => {
			console.log('[Site-wide Audit] Raw response:', response);
			return response.json();
		})
		.then(data => {
			console.log('[Site-wide Audit] API Response:', data);
			if (data.error) {
				$result.html('<span style="color:red;">Error: ' + data.error + '</span>');
				console.warn('[Site-wide Audit] API error:', data.error);
			} else {
				// Modern UI rendering
				let html = '<div class="vrt-audit-result">';
				html += '<div class="vrt-audit-header">';
				html += '<h1>Website Comparison Test Results</h1>';
				html += '<p>Comparison between test and reference domains</p>';
				html += '</div>';
				// Summary
				html += '<div class="vrt-audit-summary">';
				html += '<div class="vrt-audit-summary-card"><h3>Matching URLs</h3><div class="value">' + (data.matchingUrls ? data.matchingUrls.length : 0) + '</div></div>';
				html += '<div class="vrt-audit-summary-card"><h3>Missing in Test</h3><div class="value">' + (data.missingUrls && data.missingUrls.inTest ? data.missingUrls.inTest.length : 0) + '</div></div>';
				html += '<div class="vrt-audit-summary-card"><h3>Missing in Reference</h3><div class="value">' + (data.missingUrls && data.missingUrls.inRef ? data.missingUrls.inRef.length : 0) + '</div></div>';
				let passed = 0, total = 0;
				if (Array.isArray(data.testresults)) {
					total = data.testresults.length;
					passed = data.testresults.filter(tr => tr.passed).length;
				}
				html += '<div class="vrt-audit-summary-card"><h3>Visual Tests</h3><div class="value">' + passed + '/' + total + ' Passed</div></div>';
				html += '</div>';
				// Domains
				html += '<div class="vrt-audit-domains">';
				html += '<div class="vrt-audit-domain-card"><h3>Test Domain</h3><a href="' + (data.testDomain || '#') + '" target="_blank">' + (data.testDomain || '-') + '</a></div>';
				html += '<div class="vrt-audit-domain-card"><h3>Reference Domain</h3><a href="' + (data.refDomain || '#') + '" target="_blank">' + (data.refDomain || '-') + '</a></div>';
				html += '</div>';
				// Matching URLs
				html += '<div class="vrt-audit-section"><h2>Matching URLs</h2><hr style="margin-bottom:20px;">';
				html += '<div class="vrt-audit-url-list">';
				if (Array.isArray(data.matchingUrls) && data.matchingUrls.length) {
					data.matchingUrls.forEach(url => {
						html += '<div class="vrt-audit-url-item">' + url + '</div>';
					});
				} else {
					html += '<div class="vrt-audit-url-item">None</div>';
				}
				html += '</div></div>';
				// Missing URLs
				html += '<div class="vrt-audit-section"><h2>Missing URLs</h2><hr style="margin-bottom:20px;">';
				html += '<div class="vrt-audit-missing-container">';
				html += '<div class="vrt-audit-missing-section"><h3>Missing in Test Domain</h3><div class="vrt-audit-url-list">';
				if (data.missingUrls && Array.isArray(data.missingUrls.inTest) && data.missingUrls.inTest.length) {
					data.missingUrls.inTest.forEach(url => {
						html += '<div class="vrt-audit-url-item">' + url + '</div>';
					});
				} else {
					html += '<div class="vrt-audit-url-item">None</div>';
				}
				html += '</div></div>';
				html += '<div class="vrt-audit-missing-section"><h3>Missing in Reference Domain</h3><div class="vrt-audit-url-list">';
				if (data.missingUrls && Array.isArray(data.missingUrls.inRef) && data.missingUrls.inRef.length) {
					data.missingUrls.inRef.forEach(url => {
						html += '<div class="vrt-audit-url-item">' + url + '</div>';
					});
				} else {
					html += '<div class="vrt-audit-url-item">None</div>';
				}
				html += '</div></div>';
				html += '</div></div>';
				// Visual Test Results
				html += '<div class="vrt-audit-section"><h2>Visual Test Results</h2><hr style="margin-bottom:20px;">';
				html += '<div style="width:100%;overflow-x:auto;">';
				html += '<table class="vrt-audit-test-results" style="width:100%;"><thead><tr><th>Path</th><th>Status</th><th>Report</th></tr></thead><tbody>';
				if (Array.isArray(data.testresults) && data.testresults.length) {
					data.testresults.forEach(tr => {
						html += '<tr>';
						html += '<td>' + (tr.path || '-') + '</td>';
						html += '<td><span class="vrt-audit-status ' + (tr.passed ? 'vrt-audit-status-passed' : 'vrt-audit-status-failed') + '">' + (tr.passed ? 'Passed' : 'Failed') + '</span></td>';
						html += '<td>';
						if (tr.reportUrl) {
							html += '<a href="' + (tr.reportUrl.startsWith('http') ? tr.reportUrl : ('http://localhost:3000' + tr.reportUrl)) + '" class="vrt-audit-report-link" target="_blank">View Report</a>';
						} else {
							html += '-';
						}
						html += '</td>';
						html += '</tr>';
					});
				} else {
					html += '<tr><td colspan="3">No visual test results.</td></tr>';
				}
				html += '</tbody></table></div></div>';
				// All URLs
				html += '<div class="vrt-audit-section"><h2>All URLs</h2>';
				html += '<div class="vrt-audit-missing-container">';
				html += '<div class="vrt-audit-missing-section"><h3>Reference Domain URLs</h3><div class="vrt-audit-url-list">';
				if (Array.isArray(data.allRefUrls) && data.allRefUrls.length) {
					data.allRefUrls.forEach(url => {
						html += '<div class="vrt-audit-url-item">' + url + '</div>';
					});
				} else {
					html += '<div class="vrt-audit-url-item">None</div>';
				}
				html += '</div></div>';
				html += '<div class="vrt-audit-missing-section"><h3>Test Domain URLs</h3><div class="vrt-audit-url-list">';
				if (Array.isArray(data.allTestUrls) && data.allTestUrls.length) {
					data.allTestUrls.forEach(url => {
						html += '<div class="vrt-audit-url-item">' + url + '</div>';
					});
				} else {
					html += '<div class="vrt-audit-url-item">None</div>';
				}
				html += '</div></div>';
				html += '</div></div>';
				html += '</div>';
				$result.html(html).css({background:'none',padding:0,border:'none','border-radius':'0'});
				console.log('[Site-wide Audit] Result displayed in UI (modern)');
			}
		})
		.catch(error => {
			$result.html('<span style="color:red;">API request failed.</span>');
			console.error('[Site-wide Audit] API Error:', error);
		})
		.finally(() => {
			$button.prop('disabled', false);
			console.log('[Site-wide Audit] Button re-enabled');
		});
	});
});
</script>