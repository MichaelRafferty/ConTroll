function getBreakdown () {

var conid=$('#conid').val();
var script = "scripts/getStats.php";
$.ajax({
  url: script,
  method: "GET",
  data: {'method': 'attendance', 'conid': conid},
  success: function(data, textStatus, jhXHR) {
    //showError('trace:', data);
    d3.select("svg").remove();
    buildBreakdown(data['badgeList']);
    buildGraph(data['histogram'], data['staffing'], data['con']);
    return false;
  },
  error: function(jqXHR, textStatus, errorThrown) {
    showError("ERROR in " + script + ": " + textStatus, jqXHR);
  }
});
}

function buildGraph(data, staff, con) {
    var svg = d3.select("#graphs").append("svg");
    var numCharts = 3;
    var parentWidth = $("#graphs").width();

    var margin = { top: 20, right: 50, bottom: 40, left: 50 },
        width = (.9 * parentWidth) - margin.left - margin.right,
        baseHeight = .33 * parentWidth - margin.bottom - margin.top / numCharts;

    var activityHeight = baseHeight / 2;
    var cummHeight = baseHeight;

    var totalHeight = (numCharts - 1) * activityHeight + cummHeight + margin.top + (numCharts * margin.bottom);

  // condition data
    data.forEach(function (d) {
        d['expired'] = +d['expired'];
        d['oneday'] = +d['oneday'];
        d['full'] = +d['full'];
        d['badge'] = +d['badge'];
        d['trans'] = +d['trans'];
        if (d['time'] < "2016-05-26 17:00:00") {
            d['time'] = "2016-05-26 17:00:00";
        }
        d['time']=Date.parse(d['time']);
    });
    staff.forEach(function (d) {
        // d['time']=Date.parse(d['t'].replace(" ", "T")+"Z");
        d['time']=Date.parse(d['t']);
        d['time']=Date.parse(d['t']);
      
        d['de'] = +d['de'];
        d['reg'] = +d['reg'];
    });

    //con['start']=Date.parse(con['startdate'].replace(" ", "T") + "Z");
    con['start']=Date.parse(con['startdate']);
    //con['end']=Date.parse(con['enddate'].replace(" ", "T") + "Z");
    con['end']=Date.parse(con['enddate']);
    

    //establish canvas
    svg.attr('width', width + margin.left + margin.right)
        .attr('height', totalHeight);

    var canvas = {
        cumm: svg.append("g").attr('transform',
            'translate(' + margin.left + ', ' + margin.top + ')'),
        activity: svg.append("g").attr('transform',
            'translate(' + margin.left + ', ' + (+margin.top + cummHeight + margin.bottom) + ')'),
        staff: svg.append("g").attr('transform',
            'translate(' + margin.left + ', ' + (+margin.top + cummHeight + activityHeight + 2 * margin.bottom) + ')'),
    };


  // define scales and axes
  var x = d3.time.scale().range([0, width]);
  x.domain(d3.extent(data, function(d) { return d['time']; }))
  var xMin = d3.min(data, function(d) { return d['time']; });
  var xMax = d3.max(data, function(d) { return d['time']; });
  var xInterval = d3.time.minutes(xMin, con['end'], 15);
  x.domain(d3.extent(xInterval, function(d) { return Date.parse(d); }))
  var badgeData = xInterval.map(function(bucket) {
    return _.find(data, function(d) { return d['time']==Date.parse(bucket); }) 
             || {time: Date.parse(bucket), badge: 0, trans:0};
  });
  var full = 0, oneday=0, expired=0;
  var cummData = xInterval.map(function(bucket) {
    var hold = _.find(data, function(d) { return d['time']==Date.parse(bucket); });
    if(hold) { 
      full = hold['full']; 
      oneday= hold['oneday']; 
      expired=hold['expired']; 
      return hold;
    } else {
      return { time: Date.parse(bucket), full: full, oneday: oneday, expired: expired};
    }
  });
  var staffData = xInterval.map(function(bucket) {
    return _.find(staff, function(d) { return d['time']==Date.parse(bucket); }) 
             || {time: Date.parse(bucket), de: 0, reg:0};
  });

  var yStaffMax = d3.max(staff, function(d) { return Math.max(d['de'], d['reg']); });

  var yActivityMax = d3.max(data, function(d) { return Math.max(d['badge'], d['trans']); });
  var yCummMax = d3.max(data, function(d) { return d['expired']+d['oneday']+d['full']; });

  var yStaff = d3.scale.linear().range([activityHeight, 0]);
  yStaff.domain([0, yStaffMax]);

  var yActivity = d3.scale.linear().range([activityHeight, 0]);
  yActivity.domain([0, yActivityMax]);

  var yCumm = d3.scale.linear().range([cummHeight, 0]);
  yCumm.domain([0, yCummMax]);

  var xAxis = d3.svg.axis().scale(x).orient("bottom");
  var yStaffAxis = d3.svg.axis().scale(yStaff).orient("left");
  var yActivityAxis = d3.svg.axis().scale(yActivity).orient("left");
  var yCummAxis = d3.svg.axis().scale(yCumm).orient("left");
  
  canvas['staff'].append("g")
    .attr("class", "x axis")
    .attr("transform", "translate(0, " + activityHeight + ")")
    .call(xAxis);

    var legend = canvas['staff'].append("g")
        .attr("class", "legend")
    legend.append("text").attr('class', 'big').text("reg staff")
    legend.append("rect").attr('class', 'lineREG')
        .attr('transform', 'translate(2, 6)')
        .attr('width',10).attr('height',2);
    legend.append("text").attr('transform', 'translate(14, 12)')
        .text('cashiers');
    legend.append("rect").attr('class', 'lineDE')
        .attr('transform', 'translate(2, 18)')
        .attr('width',10).attr('height',2);
    legend.append("text").attr('transform', 'translate(14, 24)')
        .text('checkin');

    legend = canvas['activity'].append("g")
        .attr("class", "legend")
    legend.append("text").attr('class', 'big').text("activity")
    legend.append("rect").attr('class', 'linePaid')
        .attr('transform', 'translate(2, 6)')
        .attr('width',10).attr('height',2);
    legend.append("text").attr('transform', 'translate(14, 12)')
        .text('badges');
    legend.append("rect").attr('class', 'lineAll')
        .attr('transform', 'translate(2, 18)')
        .attr('width',10).attr('height',2);
    legend.append("text").attr('transform', 'translate(14, 24)')
        .text('transactions');

    legend = canvas['cumm'].append("g")
        .attr("class", "legend")
    legend.append("text").attr('class', 'big').text("Badge Tracker")
    legend.append("rect").attr('class', 'areaExpired')
        .attr('transform', 'translate(2, 4)')
        .attr('width',10).attr('height',6);
    legend.append("text").attr('transform', 'translate(14, 12)')
        .text('Expired');
    legend.append("rect").attr('class', 'areaOneday')
        .attr('transform', 'translate(2, 16)')
        .attr('width',10).attr('height',6);
    legend.append("text").attr('transform', 'translate(14, 24)')
        .text('current 1day');
    legend.append("rect").attr('class', 'areaFull')
        .attr('transform', 'translate(2, 28)')
        .attr('width',10).attr('height',6);
    legend.append("text").attr('transform', 'translate(14, 36)')
        .text('Full Badges');

  canvas['activity'].append("g")
    .attr("class", "x axis")
    .attr("transform", "translate(0, " + activityHeight + ")")
    .call(xAxis);

  canvas['cumm'].append("g")
    .attr("class", "x axis")
    .attr("transform", "translate(0, " + cummHeight + ")")
    .call(xAxis);
  
  canvas['staff'].append("g")
    .attr("class", "y axis")
    .call(yStaffAxis);

  canvas['activity'].append("g")
    .attr("class", "y axis")
    .call(yActivityAxis);

  canvas['cumm'].append("g")
    .attr("class", "y axis")
    .call(yCummAxis);

  canvas['staff'].append("g")
    .attr("class", "y axis")
    .attr("transform", "translate("+width+",0)")
    .call(yStaffAxis);

  canvas['activity'].append("g")
    .attr("class", "y axis")
    .attr("transform", "translate("+width+",0)")
    .call(yActivityAxis);

  canvas['cumm'].append("g")
    .attr("class", "y axis")
    .attr("transform", "translate("+width+",0)")
    .call(yCummAxis);

  // staff de and reg
  var de_line = d3.svg.line()
    .x(function(d) { return x(d['time']); })
    .y(function(d) { return yStaff(d['de']); });
  canvas['staff'].append("path").datum(staffData).attr("class", "lineDE").attr("d", de_line);
  
  var reg_line = d3.svg.line()
    .x(function(d) { return x(d['time']); })
    .y(function(d) { return yStaff(d['reg']); });
  canvas['staff'].append("path").datum(staffData).attr("class", "lineREG").attr("d", reg_line);
  
  // badges + transaction /15 minute
  var badge_line = d3.svg.line()
    .x(function(d) { return x(d['time']); })
    .y(function(d) { return yActivity(d['badge']); });
  canvas['activity'].append("path").datum(badgeData).attr("class", "linePaid").attr("d", badge_line);
  
  var trans_line = d3.svg.line()
    .x(function(d) { return x(d['time']); })
    .y(function(d) { return yActivity(d['trans']); });
  canvas['activity'].append("path").datum(badgeData).attr("class", "lineAll").attr("d", trans_line);
  
  // cumulative 
  var full_area = d3.svg.area()
    .x(function(d) { return x(d['time']); })
    .y1(function(d) { return yCumm(0); })
    .y0(function(d) { return yCumm(d['full']); });

  var oneday_area = d3.svg.area()
    .x(function(d) { return x(d['time']); })
    .y1(function(d) { return yCumm(d['full']); })
    .y0(function(d) { return yCumm(d['full']+d['oneday']); });

  var expired_area = d3.svg.area()
    .x(function(d) { return x(d['time']); })
    .y1(function(d) { return yCumm(d['full']+d['oneday']); })
    .y0(function(d) { return yCumm(d['full']+d['oneday']+d['expired']); });

  canvas['cumm'].append("path").datum(cummData)
    .attr("class", "areaFull")
    .attr("d", full_area);
  canvas['cumm'].append("path").datum(cummData)
    .attr("class", "areaOneday")
    .attr("d", oneday_area);
  canvas['cumm'].append("path").datum(cummData)
    .attr("class", "areaExpired")
    .attr("d", expired_area);

}

function buildBreakdownLevel(label, ptr, data, lvl) {
  var keys = Object.keys(data);
  var next;
  var acc = 0;
  if(lvl == 2) {
    next = $(document.createElement('ul'));
    for (key in data) {
      var leaf = $(document.createElement('li')).append(key + ": " + data[key]);
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
      var sum = $(document.createElement('li')).append(label + ": " + acc);
      ptr.append(sum.append(next));
    } else { 
      acc = parseInt(buildBreakdownLevel(label, ptr, data[keys[0]], lvl+1));
    }
    return acc;
  }
}

function buildBreakdown(data) {
  var ptr = $(document.createElement('ul'));
  for(key in data) {
    buildBreakdownLevel(key, ptr, data[key], 2);
  }
  $('#membershipBreakdown').empty();
  $('#membershipBreakdown').append(ptr);
}

$(document).ready(function () {
getBreakdown();
});
