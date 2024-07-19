function redraw(table) {
    /* reset facets so they can be redraw */
    resetFacets(table);

    /* get data and row building function */
    var data = $(table).data('data');
    var rowFunc = $(table).data('rowFunc');
    var facetNames = $(table).data('filters');

    /* set max count */
    var maxCount = data.length;
    $(table + "Max").empty().append(maxCount);

    /* get page size and start position */
    var start = +$(table + 'Start').val();
    var page = +$(table + 'Size').val();

    var curr = 0;
    /* loop through values in data */
    $(table+'Body').empty();
    for(var i in data) {
        var item = data[i];

        /* set filtered and selected booleans */
        var filtered = isFiltered(item);
        var selected = isSelected(item);

        /* build facet tables */
        for(j in facetNames) {
            var facet = facetNames[j];
            var value = item[facet];
            trackFacets(table, facet, value, filtered) 
        }

        /* determine items to show */
        if(filtered) { 
            curr += 1; 
            if(curr >= start && curr <= (start + page)) {
                if(selected) {
                    $(table+'Body').append(rowFunc(item)
                        .addClass('selected')
                    );
                } else {
                    $(table+'Body').append(rowFunc(item));
                }
            }
        }
    }
    $(table + 'Vis').empty().append(curr);
    for(facet in facetNames) {
        drawFacets(table, facetNames[facet]);
    }


    var mainWidth = $('#main').width();
    var facetName = $(table).data('facets');
    var newWidth = ($(table).width() + $(facetName).width() + 15);
    var oldWidth = 0;
    while(newWidth > mainWidth) { 
        if(newWidth == oldWidth) { break; } else { oldWidth = newWidth; }
        $('#main').width(newWidth); 
        mainWidth = $('#main').width();
        facetName = $(table).data('facets');
        newWidth = ($(table).width() + $(facetName).width() + 15);
    }
    console.log("newWidth: " + newWidth + ", mainWidth: " + mainWidth);
    console.log("width: " + $('#main').width());
}

function nextPage(table) {
    var page = +$(table + 'Size').val();
    var start = +$(table + 'Start').val();
    var max = +$(table +'Vis').text();

    var newStart = Math.min(max, start+page);
    $(table + 'Start').val(newStart);

    redraw(table);
}

function prevPage(table) {
    var page = +$(table + 'Size').val();
    var start = +$(table + 'Start').val();

    var newStart = Math.max(0, start-page);
    $(table + 'Start').val(newStart);

    redraw(table);
}

function firstPage(table) {
    $(table + 'Start').val(0);

    redraw(table);
}

function lastPage(table) {
    var page = +$(table + 'Size').val();
    var max = +$(table +'Max').text();

    var newStart = Math.max(0, max-page);
    $(table + 'Start').val(newStart);

    redraw(table);
}

function resetFacets(table) {
    var filterList = $(table).data('filters');
    for(var i in filterList) {
        var name = filterList[i];
        $(table + '_filter_' + name).data('data', []);
        $(table + '_filter_' + name).data('count', 0);
        $(table + '_filter_' + name).data('width', 0);
    }
    $('.facet svg').remove();
}

function defineFacets(table, facets) {
    if($(table).data('nameMatch') != null) {
        var name = 'name';
        var facet = $(document.createElement('div'))
            .attr('id', table.substring(1) + '_filter_' + name)
            .addClass('facet')
            .append($(document.createElement('div'))
                .addClass('facetHdr')
                .append(name)
            )
            .append($(document.createElement('input'))
                .attr('type', 'text')
                .attr('id', 'name')
                .attr('size', 16)
            )
            .append($(document.createElement('span')).text(' '))
            .append($(document.createElement('button'))
                .text('Go')
                .click(function() {addNameFilter(table)})
            );
        
        $(facets).append(facet);
    }
    var filterList = $(table).data('filters');
    $(table).data('facets', facets);

    for(var i in filterList) {
        var name = filterList[i];
        var facet = $(document.createElement('div'))
            .attr('id', table.substring(1) + '_filter_' + name)
            .addClass('facet')
            .append($(document.createElement('div'))
                .addClass('facetHdr')
                .append(name)
            );
        
        $(facets).append(facet);
        $(table + '_filter_' + name).data('data', []);
        $(table + '_filter_' + name).data('count', 0);
        $(table + '_filter_' + name).data('width', 0);
    }
}

function trackFacets(table, facet, point, filtered) {
    var label = $(table + '_filter_' + facet);
    
    var data = label.data('data');
    var count = +label.data('count');
    var maxWidth = +label.data('width');

    if(data[point] == undefined) {
        data[point] = {vis: 0, full: 0};
        count +=1;
        var test = $(document.createElement('span')).append(point);
        $('#main').append(test);
        maxWidth = Math.max(maxWidth, test.width());
        test.remove();
        label.data('width', maxWidth);
    }

    data[point].full += 1;
    if(filtered) { data[point].vis +=1; }

    label.data('data', data);
    label.data('count', count);
}

function drawFacets(table, facet) {
    var lineheight = 20,
        name = table + '_filter_' + facet,
        width = +$(name).width() - 5,
        data = $(name).data('data'),
        numElem = $(name).data('count'),
        height = lineheight * numElem,
        nameWidth = $(name).data('width');

    var svg = d3.select(table + '_filter_' + facet).append('svg')
        .attr('width', width)
        .attr('height', height)
        .append('g')
            .attr('transform', 'translate(0,'+lineheight+')');

    var facets = [], orderedFacets;

    for(value in data) {
        facets.push(value);
    }

    var wellOrderedFacets = facets.sort(function(b,a) { 
        return data[a].full - data[b].full; 
    });

    var numMax = data[wellOrderedFacets[0]].full;
    orderedFacets = facets.sort();

    var test = $(document.createElement('span')).append(numMax);
    $('#main').append(test);
    var numWidth = test.width();
    test.remove();
    var rectWidth = width - nameWidth - numWidth;

    var x = d3.scale.linear()
        .domain([0, numMax])
        .range([0, rectWidth]);

    var rows = svg.selectAll('g.facets')
        .data(orderedFacets)
        .enter().append('g')
            .classed('facets', true)
            .attr('facet', facet)
            .attr('value', function(d) { return d; })
            .attr('transform', function(d,i) { 
                return 'translate(0, ' + i*lineheight + ')'; 
            })
            .on('click', function(d, i) { 
                select(table, $(this).attr('facet'), $(this).attr('value'));
            });

    rows.append('rect')
        .classed('background', true)
        .classed('even', function(d,i) { return (i%2)==1; })
        .classed('odd', function(d,i) { return (i%2)==0; })
        .attr("width", "100%")
        .attr("height", lineheight)
        .attr('y', -lineheight+4);

    rows.append('text').text(function(d) { return d; });
    rows.append('text')
        .classed('filter_num', true)
        .attr('x', width)
        .text(function(d) { return data[d].full; });

    rows.append('rect')
        .classed('vis', true)
        .attr('x', nameWidth)
        .attr('y', -lineheight+6)
        .attr('height', lineheight-4)
        .attr('width', function(d) { 
            return x(data[d].vis); 
        });

    rows.append('rect')
        .classed('full', true)
        .attr('x', function (d) { return nameWidth + x(data[d].vis); })
        .attr('y', -lineheight+6)
        .attr('height', lineheight-4)
        .attr('width', function(d) { return x(data[d].full - data[d].vis); });
    
    rows.append('text')
        .classed('filter_num', true)
        .classed('grey', function(d) { return (x(data[d].vis)>numWidth); })
        .classed('reverse', function(d) { return (x(data[d].vis)<=numWidth); })
        .attr('x', function(d) { 
            return nameWidth+x(data[d].vis); 
        })
        .text(function(d) { 
            if(data[d].vis < data[d].full) { 
                return data[d].vis; 
            }
        });
}

function filter2text(filter) {
    var ret = filter.facet;
    if(filter.inv) { ret += " != "; } else { ret += " == "; }
    ret += "{" + filter.value.join('|') + "}";

    return ret; 
}

var select_filter = {facet: "", value: [], inv: false};
var filter = [];

function select(table, facet, value) {
    if(select_filter.facet != facet) {
        clearSelect(table);
        select_filter.facet = facet;
    }
    if(select_filter.value.indexOf(value)<0) {
        select_filter.value.push(value);
    } else {
        select_filter.value.splice(select_filter.value.indexOf(value),1);
    }

    var selectedGroup = d3.select(table + '_filter_' + facet + ' g[value="' + value + '"]');
    selectedGroup.classed('selected', !selectedGroup.classed('selected'));

    $(table+' tr['+facet+'="'+value+'"]').toggleClass('selected');
    if(select_filter.value.length > 0) {
        showSelect(table);
    } else { 
        clearSelect(table);
    }
}

function clearSelect(table) { 
    select_filter = {facet: "", value: [], inv: false};
    $('.selected').removeClass('selected');
    d3.select('.selected').classed('selected', false);
    $(table + 'Select').empty();
    $(table + 'SelectWrap').hide();
}

function invSelect(table) {
    select_filter.inv = !select_filter.inv;
    $(table + ' tr').toggleClass('selected');
    showSelect(table);
}

function showSelect(table) { 
    $(table + 'Select').empty().append(filter2text(select_filter));
    $(table + 'SelectWrap').show();
}

function isSelected(row) {
    if(!select_filter.inv) {
        return(select_filter.value.indexOf(row[select_filter.facet]) >= 0)
    } else {
        return(select_filter.value.indexOf(row[select_filter.facet]) < 0)
    }
}

function addFilter(table) {
    var filt = select_filter;
    var num = filter.length;
    filter.push({
        facet: filt.facet,
        value: filt.value,
        inv: filt.inv,
        num: num,
        active: true
    });
    $(table + 'Filter')
        .append($(document.createElement('span'))
            .attr('filternum', num)
            .append(filter2text(filt))
            .append($(document.createElement('button'))
                .append('X')
                .on('click', function () { removeFilter(table, num); })
            )
        );

    clearSelect(table);
    redraw(table);
}

function removeFilter(table, num) {
    filter[num].active = false;
    $(table + 'Filter span[filternum='+num+']').remove();
    redraw(table);
}

function isFiltered(row) {
    var ret = true;

    for(i in filter) {
        if(filter[i].active) {
            if(filter[i].facet == 'name') {
                ret = filter[i].value($('#name').val(), row);
            } else if(filter[i].value.indexOf(row[filter[i].facet]) >= 0) {
                ret = !filter[i].inv;
            } else {
                ret = filter[i].inv;
            }
        }

        if(!ret) { return ret; } 
    }

    return ret;
}

function addNameFilter(table) {
    var num = filter.length;
    filter.push({
        facet: 'name',
        value: $(table).data('nameMatch'),
        inv: false,
        num: num,
        active: true
    });
    $(table + 'Filter')
        .append($(document.createElement('span'))
            .attr('filternum', num)
            .append('name matches ' + $('#name').val())
            .append($(document.createElement('button'))
                .append('X')
                .on('click', function () { removeFilter(table, num); })
            )
        );

    redraw(table);
}
