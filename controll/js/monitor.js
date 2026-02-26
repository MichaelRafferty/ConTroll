function getBreakdown () {

var conid=$('#conid').val();
var script = "scripts/getStats.php";
$.ajax({
  url: script,
  method: "GET",
  data: {'method': 'attendance', 'conid': conid},
  success: function(data, textStatus, jhXHR) {
    //showError('trace:', data);
    checkRefresh(data);
    buildBreakdown(data['badgeList']);
    if(data['histogram'].length > 0) { buildThroughput(data['histogram'], data['con']); }
    if(data['histogram'].length > 0) { buildOnSite(data['histogram'], data['con'], data['total']); }
    if(data['staffing'].length > 0) { buildStaffing(data['staffing'], data['con'], data['total']); }
    return false;
  },
  error: function(jqXHR, textStatus, errorThrown) {
    showError("ERROR in " + script + ": " + textStatus, jqXHR);
  }
});
}

function formatDate(date) {
    var year = date.getYear()+1900;
    var month = date.getMonth()+1;
    var day = date.getDate();
    var hour = date.getHours().toString().padStart(2,"0");
    var minute =date.getMinutes().toString().padStart(2,"0");
    var second = date.getSeconds().toString().padStart(2,"0");

    return year + '-' + month + '-' + day + ' ' + hour + ':' + minute + ':' + second;
}

function buildOnSite(data, con, total) {
    var traces = Array();

    var fullOnSite = {'name': 'Full', x:[], y:[], stackgroup: 'one',
        fillcolor: 'rgba(128,0,128,1)',
        hoveron: 'points+fills',
        line: { color: 'rgba(128,0,128,1)' },
        showlegend: true
    };

    var onedayOnSite = {'name': 'One-Day', x:[], y:[], stackgroup: 'one',
        fillcolor: 'rgba(128,128,255,1)',
        hoveron: 'points+fills',
        line: { color: 'rgba(128,128,255,1)' },
        showlegend: true
    }
    
    var fullCount = 0;
    var onedayCount = 0;

    for(const arr in data) {
        dataline = data[arr];

        if(!dataline.full) { dataline.full=fullCount; }
        else { fullCount = dataline.full; }
        if(!dataline.oneday) { dataline.oneday=onedayCount; }
        else { onedayCount = dataline.oneday; }

        fullOnSite.x.push(dataline.time);
        onedayOnSite.x.push(dataline.time);
        fullOnSite.y.push(dataline.full);
        onedayOnSite.y.push(dataline.oneday);
    }

    traces.push(fullOnSite);
    traces.push(onedayOnSite);

    var shapes = Array(); 
    shapes.push({
        type: 'line',
        x0: con.startdate, x1: con.startdate, y0: 0, y1: con.paid_members,
        line: {color: 'grey', size: 1, dash: 'dash'},
        label: {text: 'Convention Start'}
    });
    shapes.push({
        type: 'line',
        x0: con.enddate, x1: con.enddate, y0: 0, y1: con.paid_members,
        line: {color: 'grey', size: 1, dash: 'dash'},
        label: {text: 'Convention End'}
    });

    Plotly.newPlot('tracker', traces, 
        {title: 'Printed Badges', autosize: true, shapes: shapes},
        {responsive: true});
}

function buildStaffing(data, con) {
    var staffing = Array();
   
    var cashLine = {'name': 'Cashier', x:[], y:[], mode:'lines',
        line: {
            color: 'rgba(0, 255, 0, 1)',
            size: 3,
        },
        showlegend: true,
    }

    var checkLine = {'name': 'Checkin', x:[], y:[], mode:'lines',
        line: {
            color: 'rgba(0, 0, 0, 1)',
            size: 3,
        },
        showlegend: true,
    }

    var max  = 0;
    var lasttime;

    for(const arr in data) {
        dataline = data[arr];
	if(dataline.log_time == null) { continue; }

        var newtime = new Date(dataline.log_time);
        if(!lasttime) { lasttime = newtime; }

        var expectedtime = new Date(lasttime.getTime() + 15 * 60000);
        if(expectedtime < newtime) {
            interval_start = expectedtime;
            lasttime = newtime;
            interval_end = new Date(lasttime.getTime() - 15 * 60000);

            cashLine.x.push(formatDate(interval_start));
            checkLine.x.push(formatDate(interval_start));
            cashLine.y.push(0); checkLine.y.push(0);

            cashLine.x.push(formatDate(interval_end));
            checkLine.x.push(formatDate(interval_end));
            cashLine.y.push(0); checkLine.y.push(0);
        } else {
            lasttime=newtime;
        }

        cashLine.x.push(dataline.log_time);
        checkLine.x.push(dataline.log_time);
        cashLine.y.push(dataline.cashier);
        checkLine.y.push(dataline.checkin);
    }

    staffing.push(checkLine);
    staffing.push(cashLine);

    var shapes = Array(); 
    shapes.push({
        type: 'line',
        x0: con.startdate, x1: con.startdate, y0: 0, y1: con.max_staff,
        line: {color: 'grey', size: 1, dash: 'dash'},
        label: {text: 'Convention Start'}
    });
    shapes.push({
        type: 'line',
        x0: con.enddate, x1: con.enddate, y0: 0, y1: con.max_staff,
        line: {color: 'grey', size: 1, dash: 'dash'},
        label: {text: 'Convention End'}
    });

    Plotly.newPlot('staffing', staffing, 
        {title: 'Registration Staffing', autosize: true, shapes: shapes},
        {responsive: true});
}

function buildThroughput(data, con) {
    var throughput = Array();
   
    var badgeLine = {'name': 'Badges', x:[], y:[], mode:'lines',
        line: {
            color: 'rgba(255, 0, 128, 1)',
            size: 3,
        },
        showlegend: true,
    }

    var transLine = {'name': 'Transactions', x:[], y:[], mode:'lines',
        line: {
            color: 'rgba(0, 0, 255, 1)',
            size: 3,
        },
        showlegend: true,
    }

    var max  = 0;
    var lasttime;

    for(const arr in data) {
        dataline = data[arr];

        var newtime = new Date(dataline.time);
        if(!lasttime) { lasttime = newtime; }

        var expectedtime = new Date(lasttime.getTime() + 15 * 60000);
        if(expectedtime < newtime) {
            interval_start = expectedtime;
            lasttime = newtime;
            interval_end = new Date(lasttime.getTime() - 15 * 60000);

            badgeLine.x.push(formatDate(interval_start));
            transLine.x.push(formatDate(interval_start));
            badgeLine.y.push(0); transLine.y.push(0);

            badgeLine.x.push(formatDate(interval_end));
            transLine.x.push(formatDate(interval_end));
            badgeLine.y.push(0); transLine.y.push(0);
        } else {
            lasttime=newtime;
        }

        badgeLine.x.push(dataline.time);
        transLine.x.push(dataline.time);
        badgeLine.y.push(dataline.badge);
        transLine.y.push(dataline.trans);
        max = Math.max(max, dataline.badge, dataline.trans);
    }

    throughput.push(badgeLine);
    throughput.push(transLine);

    var shapes = Array(); 
    shapes.push({
        type: 'line',
        x0: con.startdate, x1: con.startdate, y0: 0, y1: max,
        line: {color: 'grey', size: 1, dash: 'dash'},
        label: {text: 'Convention Start'}
    });
    shapes.push({
        type: 'line',
        x0: con.enddate, x1: con.enddate, y0: 0, y1: max,
        line: {color: 'grey', size: 1, dash: 'dash'},
        label: {text: 'Convention End'}
    });

    Plotly.newPlot('throughput', throughput, 
        {title: 'Registration Activity', autosize: true, shapes: shapes},
        {responsive: true});
}

function buildBreakdownLevel(label, ptr, data, lvl) {
  var keys = Object.keys(data);
  var next;
  var acc = {printed: 0, total: 0};
  if(lvl == 2) {
    next = $(document.createElement('ul'));
    for (key in data) {
        var leaf = $(document.createElement('li')).append(key + ": " + data[key]['printed'] + " of " + data[key]['total']);
        next.append(leaf);

        acc['printed'] += parseInt(data[key]['printed']);
        acc['total'] += parseInt(data[key]['total']);
    }
    var sum = $(document.createElement('li')).append(label + ": " + acc['printed'] + " of " + acc['total']);
    ptr.append(sum.append(next));
    return acc;
  } else {
    if(keys.length > 1) {
      next = $(document.createElement('ul'));
      for (key in data) {
        var tot = parseInt(buildBreakdownLevel(key, next, data[key], lvl+1));
          acc['printed'] += parseInt(tot['printed']);
          acc['total'] += parseInt(tot['total']);
      }
      var sum = $(document.createElement('li')).append(label + ": " + acc['printed'] + " of " + acc['total']);
      ptr.append(sum.append(next));
    } else { 
      acc = buildBreakdownLevel(label, ptr, data[keys[0]], lvl+1);
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
