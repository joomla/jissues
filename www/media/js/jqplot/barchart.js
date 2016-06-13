/**
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

if (typeof jQuery === 'undefined') {
    throw new Error('JQPLOTBarchart JavaScript requires jQuery')
}

!function ($) {
    'use strict';

    $.JQPLOTBarchart = function (containerId, urlId, barDirection, stackSeries, barMargin) {
        $.jqplot.config.enablePlugins = true;

        // The url for our json data
        var jsonurl = $('#' + urlId).attr('data-href');

        var drawjqChart = function (url, tag) {
            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                success: onDataReceived
            });

            function onDataReceived(response, textStatus, jqXHR) {
                var series = response.data,
                    chartData = series[0],
                    chartTicks = series[1],
                    chartLabels = series[2],
                    title = series[3],
                    axisLabel = '';

                if (series.length == 5) {
                    axisLabel = series[4];
                }

                // Swap axis if bardirection is horizontal
                var xaxis = {renderer: $.jqplot.CategoryAxisRenderer, ticks: chartTicks, label: axisLabel},
                    yaxis = {padMin: 0, pad: 1.05, min: 0},
                    highlighterAxis = 'y';

                if (barDirection == 'horizontal') {
                    var temp = yaxis;
                    yaxis = xaxis;
                    xaxis = temp;
                    highlighterAxis = 'x';
                }

                var plot2 = $.jqplot(containerId, chartData, {
                    title: title,
                    stackSeries: stackSeries,
                    // The "seriesDefaults" option is an options object that will be applied to all series in the chart.
                    seriesDefaults: {
                        renderer: $.jqplot.BarRenderer,
                        rendererOptions: {
                            fillToZero: true,
                            barDirection: barDirection,
                            barMargin: barMargin
                        },
                        pointLabels: {show: false}
                    },
                    // Custom labels for the series are specified with the "label" option on the series option. Here a series option object is specified for each series.
                    series: chartLabels,
                    // Show the legend and put it outside the grid, but inside the plot container, shrinking the grid to accomodate the legend.
                    // A value of "outside" would not shrink the grid and allow the legend to overflow the container.
                    legend: {
                        show: true,
                        placement: 'outsideGrid'
                    },
                    axes: {
                        // Use a category axis on the x axis and use our custom ticks.
                        xaxis: xaxis,
                        yaxis: yaxis
                    },
                    highlighter: {
                        show: true,
                        tooltipAxes: highlighterAxis,
                        sizeAdjust: 5,
                        tooltipLocation: 'ne',
                        fadeTooltip: true,
                        tooltipFadeSpeed: 'slow',
                        formatString: '<h4>%s</h4>'
                    }
                });
                plot2.redraw();
            };
        };

        drawjqChart(jsonurl, containerId);
    }
}(jQuery);
