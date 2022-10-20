$(document).ready(function () {
    //test("POST", "test=true", '#test');
    getBreakdown();
});
function getDailyTrend() {
    var script= "scripts/getStats.php";
    $.ajax({
        url: script,
        method: "GET",
        data: {'method': 'preConTrend'},
        success: function(data, textStatus, jqXhr) {
            if(data['error']) {
                alert(data['error']);
                console.log(JSON.stringify(data, null, 2));
            } else {
                //console.log(JSON.stringify(data, null, 2));
                buildDailyTrend(data['statArray'], +data['today'], 'paid');
                return false;
            }
        }
    });
}
function getOverTime() {
    var script= "scripts/getStats.php";
    $.ajax({
        url: script,
        method: "GET",
        data: {'method': 'totalMembership'},
        success: function(data, textStatus, jqXhr) {
            if(data['error']) {
                alert(data['error']);
                console.log(JSON.stringify(data, null, 2));
            } else {
                //console.log(JSON.stringify(data, null, 2));
                buildOverTime(data['maxReg']);
                return false;
            }
        }
    });
}
function getBreakdown() {
    var script= "scripts/getStats.php";
    $.ajax({
        url: script,
        method: "GET",
        data: {'method': 'overview'},
        success: function(data, textStatus, jqXhr) {
            if(data['error']) {
                alert(data['error']);
                console.log(JSON.stringify(data, null, 2));
            } else {
                //console.log(JSON.stringify(data, null, 2));
                var ptr = $(document.createElement('ul'));
                var overview = data['overview']
                var key;
                $('#membershipBreakdown').append(ptr);
                for(key in overview) {
                    buildBreakdownLevel(key, ptr, overview[key], 2);
                }
                if (data['today']) {
                    var ptr = $(document.createElement('p'));
                    ptr.html(data['today'] + ' days until con');
                    $('#membershipBreakdown').append(ptr);
                }
                getOverTime();
                getDailyTrend();
                return false;
            }
        }
    });
}

function buildBreakdownLevel(label, ptr, data, lvl) {
    var keys = Object.keys(data);
    var next;
    var acc = 0;
    if(lvl == 4) {
        next = $(document.createElement('ul'));
        for (key in data) {
            var leaf = $(document.createElement('li'))
                .append(key + ": " + data[key]);
            next.append(leaf);

            acc += parseInt(data[key]);
        }
        var sum = $(document.createElement('li')).append(label + ": " + acc);
        ptr.append(sum.append(next));
        return acc;
    } else {
        if(keys.length > 1) {
            next = $(document.createElement('ul'));
            for (key in data) {
                var tot = parseInt(buildBreakdownLevel(key, next, data[key], lvl+1));
                acc += parseInt(tot);
            }
            var sum = $(document.createElement('li'))
                .append(label + ": " + acc);
            ptr.append(sum.append(next));
        } else {
            acc = parseInt(buildBreakdownLevel(label, ptr, data[keys[0]], lvl+1));
        }
        return acc;
    }
}

function buildOverTime(data) {
  var svg = d3.select("#OverTimeForm").append("svg");
  var parentWidth = $("#OverTime").width();
  var margin = {top: 20, right: 50, bottom: 30, left: 50},
  width = (.9 * parentWidth) - margin.left - margin.right,
  height = (.33 * parentWidth) - margin.top - margin.bottom;
  svg.attr('width', width+margin.left + margin.right)
    .attr('height', height+margin.top + margin.bottom);
  var canvas = svg.append("g").attr('transform', 'translate(' + margin.left + ', ' + margin.top + ')');


  var parseYear = d3.time.format("%Y").parse;
  data.forEach(function(d) {
      d['year_int'] = +d['year'];
      d['year'] = parseYear(d['year']);
      d['conid'] = +d['conid'];
      d['cnt_all'] = +d['cnt_all'];
      d['cnt_paid'] = +d['cnt_paid'];
});

  var x = d3.time.scale().range([0,width]);
  var y = d3.scale.linear().range([height, 0]);
  var y2 = d3.scale.linear().range([height, 0]);

  var yMax = d3.max(data, function (d) { return d['cnt_all']; });

  x.domain(d3.extent(data, function(d) { return d['year']; }));
  y.domain([0, yMax]);

  var xAxis = d3.svg.axis().scale(x).orient("bottom");
  var yAxis = d3.svg.axis().scale(y).orient("left");
  var y2Axis = d3.svg.axis().scale(y).orient("right");


  canvas.append("g")
      .attr("class", "x axis")
      .attr("transform", "translate(0," + height + ")")
      .call(xAxis);

  canvas.append("g")
      .attr("class", "y axis")
      .call(yAxis);
    console.log('doing unpaid');
  var line_unpaid = d3.svg.line()
    .x(function(d) { return x(d['year']); })
    .y(function(d) { return y(d['cnt_all'] - d['cnt_paid']); });

  canvas.append("path").datum(data).attr("class","lineUnpaid").attr("d", line_unpaid);
  canvas.append("g")
      .attr("class", "y axis")
      .attr("transform", "translate("+width+",0)")
     .call(y2Axis);

    console.log('doing all');
  var line_all = d3.svg.line()
    .x(function(d) { return x(d['year']); })
    .y(function(d) { return y(d['cnt_all']); });
    console.log('doing paid');
  var line_paid = d3.svg.line()
    .x(function(d) { return x(d['year']); })
    .y(function(d) { return y(d['cnt_paid']); });
    console.log('doing appends');
  canvas.append("path").datum(data).attr("class","lineAll").attr("d", line_all);
  canvas.append("path").datum(data).attr("class","linePaid").attr("d", line_paid);

}


function buildDailyTrend(data, currDay, set) {
  var svg = d3.select("#membershipGrowthForm").append("svg")
  var parentWidth = $("#membershipGrowth").width();
  var margin = {top: 20, right: 50, bottom: 30, left: 50},
  width = (.9 * parentWidth) - margin.left - margin.right,
  height = (.33 * parentWidth) - margin.top - margin.bottom;

  svg.attr('width', width+margin.left + margin.right)
    .attr('height', height+margin.top + margin.bottom);
  var canvas = svg.append("g").attr('transform', 'translate(' + margin.left + ', ' + margin.top + ')');

  data.forEach(function(d) {
    d['c_paid']=+d['c_paid'];
    d['c_all']=+d['c_all'];
    d['day']=+d['day'];
    d[set]['count']=+d[set]['count'];
    d[set]['min']=+d[set]['min'];
    d[set]['lower']=+d[set]['lower'];
    d[set]['Q1']=+d[set]['Q1'];
    d[set]['med']=+d[set]['med'];
    d[set]['Q3']=+d[set]['Q3'];
    d[set]['upper']=+d[set]['upper'];
    d[set]['max']=+d[set]['max'];
  });

  var y = d3.scale.linear().range([height, 0]);
  var x = d3.scale.linear().range([0, width]);
  var yMax = d3.max(data, function(d) { return d[set]['max']; });
  var yMax2 = d3.max(data, function(d) { return d['c_'+set]; })
  x.domain(d3.extent(data, function(d) { return d['day']; }));
  y.domain([0, Math.max(yMax,yMax2)]);

  var xAxis = d3.svg.axis().scale(x).orient("bottom");
  var yAxis = d3.svg.axis().scale(y).orient("left");
  var yAxis2 = d3.svg.axis().scale(y).orient("right");

  canvas.append("g")
    .attr("class", "x axis")
    .attr("transform", "translate(0," + height + ")")
    .call(xAxis);

  canvas.append("g")
    .attr("class", "y axis")
    .attr("transform", "translate("+width+",0)")
    .call(yAxis2);

  canvas.append("g")
    .attr("class", "y axis")
    .call(yAxis);

  var area_top = d3.svg.area()
    .interpolate("monotone")
    .x(function(d) { return x(d['day']); })
    .y0(function(d) { return y(d[set]['Q3']); })
    .y1(function(d) { return y(d[set]['upper']); });
  canvas.append("path").datum(data).attr("class","areaUpper").attr("d", area_top);

  var area_mid = d3.svg.area()
    .interpolate("monotone")
    .x(function(d) { return x(d['day']); })
    .y0(function(d) { return y(d[set]['Q1']); })
    .y1(function(d) { return y(d[set]['Q3']); });
  canvas.append("path").datum(data).attr("class","areaMid").attr("d", area_mid);

  var area_bottom = d3.svg.area()
    .interpolate("monotone")
    .x(function(d) { return x(d['day']); })
    .y0(function(d) { return y(d[set]['lower']); })
    .y1(function(d) { return y(d[set]['Q1']); });
  canvas.append("path").datum(data).attr("class","areaLower").attr("d", area_bottom);

  var line_max = d3.svg.line()
    .x(function(d) { return x(d['day']); })
    .y(function(d) { return y(d[set]['max']); });
  canvas.append("path").datum(data).attr("class","lineMax").attr("d", line_max);

  var line_med = d3.svg.line()
    .x(function(d) { return x(d['day']); })
    .y(function(d) { return y(d[set]['med']); });
  canvas.append("path").datum(data).attr("class","lineMed").attr("d", line_med);

  var line_min = d3.svg.line()
    .x(function(d) { return x(d['day']); })
    .y(function(d) { return y(d[set]['min']); });
  canvas.append("path").datum(data).attr("class","lineMin").attr("d", line_min);

  var line_all = d3.svg.line()
    .x(function(d) { return x(d['day']); })
    .y(function(d) { return y(d['c_all']); });
  var line_paid = d3.svg.line()
    .x(function(d) { return x(d['day']); })
    .y(function(d) { return y(d['c_paid']); });
  if(set == 'paid') {
    canvas.append("path").datum(data).attr("class","linePaid").attr("d", line_paid);
  } else {
    canvas.append("path").datum(data).attr("class","lineAll").attr("d", line_all);
  }


}


