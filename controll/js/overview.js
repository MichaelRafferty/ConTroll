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
                buildDaily(data['dailyHistory'], data['today']);
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
                buildAnnual(data['maxReg'], data['today']);
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
                showError(data['error']);
                console.log(JSON.stringify(data, null, 2));
            } else {
                //console.log(JSON.stringify(data, null, 2));
                var ptr = $(document.createElement('ul'));
                var overview = data['overview']
                var key;
                $('#membershipBreakdown').append(ptr);
                for(key in overview) {
                    var dispLvl = 0
                    switch(key) {
                        case 'paid':
                        case 'plan':
                            dispLvl=0;
                            break;
                        default:
                            dispLvl = 1;
                    }
                    buildBreakdownLevel(key, ptr, overview[key], 1, dispLvl);
                }
                if (data['today'] && (data['today']>0)) {
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

function buildBreakdownLevel(label, ptr, data, lvl, dispLvl=0) {
    var keys = Object.keys(data);
    var next;
    var acc = 0;
    if(lvl == 2) {
        next = $(document.createElement('ul'));
        for (key in data) {
            var leaf = $(document.createElement('li'))
                .append(key + ": " + data[key]);
            next.append(leaf);

            acc += parseInt(data[key]);
        }
        if((dispLvl == 0) || (dispLvl>=lvl)) {
            var sum = $(document.createElement('li')).append(label + ": " + acc);
            ptr.append(sum.append(next));
        }
        return acc;
    } else {
        if(keys.length > 1) {
            next = $(document.createElement('ul'));
            for (key in data) {
                var tot = parseInt(buildBreakdownLevel(key, next, data[key], lvl+1, dispLvl));
                acc += parseInt(tot);
            }
            if((dispLvl == 0) || (dispLvl>=lvl)) {
                var sum = $(document.createElement('li')).append(label + ": " + acc);
                ptr.append(sum.append(next));
            }
        } else {
            acc = parseInt(buildBreakdownLevel(label, ptr, data[keys[0]], lvl+1, dispLvl));
        }
        return acc;
    }
}

function buildAnnual(annualRegCounts) {
    var allReg = {'name':'all', 'x':[], 'y':[]};
    var paidReg = {'name': 'paid','x':[], 'y':[]};
    var freeReg = {'name': 'free', 'x':[], 'y':[]};

    for(const year in annualRegCounts) {
        allReg.x.push(annualRegCounts[year]['conid']);
        allReg.y.push(annualRegCounts[year]['cnt_all']);

        paidReg.x.push(annualRegCounts[year]['conid']);
        paidReg.y.push(annualRegCounts[year]['cnt_paid']);

        freeReg.x.push(annualRegCounts[year]['conid']);
        freeReg.y.push(annualRegCounts[year]['cnt_all'] - annualRegCounts[year]['cnt_paid']);
    }

    Plotly.newPlot('AnnualMemberships', [allReg, paidReg, freeReg], {'title':'Memberships By Year', autosize:true, xaxis: {dtick:1}}, {responsive:true});
}

function buildDaily(dailyRegCounts, today) {
    var daily = Array();

    var max = 0;


    for(const year in dailyRegCounts) {
        var color = '';
        var weight = '';
        var legend = false;
        if(year == config['conid']) {
            color = 'rgba(128,0,128,1)';
            weight = 3;
            legend = true;
        } else if(year == 'mean') {
            color = 'black';
            weight = 1;
            legend = true;
        } else {
            color = 'rgba(173,216,230,' + (0.76 - (config['conid']-year)*0.01) + ')';
            weight = 1;
        }
        var byYear = dailyRegCounts[year]; byYear.reverse();
        var yearLine = {'name': year,
            x:[], y:[],
            mode:'lines',
            line:{
                color: color,
                size: weight
                },
            showlegend: legend
            };
        var acc = 0;

        for(const arr in byYear) {
            yearLine.x.push(-byYear[arr].x);
            acc += byYear[arr].y;
            if(acc > max) { max = acc; }
            yearLine.y.push(acc);
        }

        daily.push(yearLine);
    }

    var shapes = Array();
    today = -today;
    var str = " days pre-con"
    if(today > 0) { str = " days after con start"; }
    shapes.push({
        type: 'line',
        x0: today, y0: 0, x1: today, y1: max,
        line: {color: 'grey', size: 1, dash: 'dash'},
        label: {text: (-today) + str}
    });

    Plotly.newPlot('DailyTrend', daily, {'title':'Membership Growth by Day', autosize:true, shapes:shapes}, {responsive:true});
}


