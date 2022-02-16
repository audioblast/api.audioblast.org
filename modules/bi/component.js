const biAB = {
  name: 'bi',
  exec: {
    source: null,
    id: null,
    canRender: true,
    renderDiv: null,
    controlDiv: null,
    activeTab: "table",
    axisX: null,
    currentTime: 0,
    cname: null,
    dataRequested: null,
    data: null,
    setCName: function(cname) {
      this.cname = cname;
    },
    setFile: function(source, id) {
      this.source = source;
      this.id = id;
      this.data = null;
      this.dataRequested = null;
      if (this.renderDiv != null) {
        document.getElementById(this.renderDiv).innerHTML = "Loading...";
      }
      this.doRender();
    },
    setRenderDiv: function(div) {
      this.renderDiv = div;
      this.doRender();
    },
    setTab: function(tab) {
      this.activeTab = tab;
      this.doRender();
      this.doRenderControl();
    },
    setControlDiv: function(div) {
      this.controlDiv = div;
      this.doRenderControl();
    },
    doRenderControl: function() {
      var controller = document.getElementById(this.controlDiv);
      controller.innerHTML = "<a onclick=\"viewAB.setTab('"+this.cname+"','table')\">Table</a><br/><a onclick=\"viewAB.setTab('"+this.cname+"','chart')\">Chart</a>";
    },
    doRender: function() {
      if (this.data == null) {
        if (this.dataRequested == null) {
          this.dataRequested = fetch("https://api.audioblast.org/analysis/bi/?id="+this.id+"&source="+this.source+"&output=nakedJSON")
          .then(res => res.json())
          .then(data => {
            this.data = data;
            this.doRender();
          })
          .catch(function (error) {
            document.getElementById(this.renderDiv).innerHTML = "Error: " + error;
          });
        }
        return 0;
      }
      if (this.activeTab == "table") {
        var element = document.getElementById(this.renderDiv);
        if (element != null) {
          Plotly.purge(this.renderDiv);
          element.classList.remove("js-plotly-plot");
        }
        if (this.axisX == null) {
          var scrollTo = null;
        } else {
          var scrollTo = this.axisX[0]-this.axisX[0]%30;
        }
        generateAnalysisTabulator("#"+this.renderDiv, "bi", this.source, this.id, this.data, scrollTo);
      }
      if (this.activeTab == "chart") {
        delete Tabulator.findTable("#"+this.renderDiv)[0];
        var element = document.getElementById(this.renderDiv);
        element.classList.remove("tabulator");

        var startTime = [];
        var value = [];
        for (i=0; i < this.data.length; i++) {
          startTime.push(this.data[i].startTime);
          value.push(this.data[i].value);
        }
        document.getElementById(this.renderDiv).innerHTML = "";
        var layout = {margin: {l: 0,r: 0,b: 0,t: 0,pad: 0}, bargap:0};
        layout['xaxis'] = {
          range: this.axisX
        };
        layout['shapes'] =  [{
          //line for scroll
          xref: 'x',
          yref: 'paper',
          type: 'line',
          x0: 0,
          y0: 0,
          x1: 0,
          y1: 2,
          line: {
            color: 'rgb(128, 0, 128)',
            width: 3
          }
        }];
       //Check that div still exists - it might have already been closed
       if (document.getElementById("#"+this.renderDiv) !== undefined) {
          Plotly.newPlot(this.renderDiv, [{x:startTime, y:value}], layout, {displayModeBar: false});
       }
      }
    },
    setCurrentTime: function(new_t, new_range) {
      this.currentTime = new_t;
      this.axisX = new_range;
      if (this.activeTab == "chart") {
        var element = document.getElementById(this.renderDiv);
        if (element != null && element.classList.contains("js-plotly-plot")) {
          Plotly.relayout(this.renderDiv, {'shapes[0].x0':this.currentTime, 'shapes[0].x1':this.currentTime, 'xaxis.range': this.axisX}, [0,1]);          }
      }
      if (this.activeTab == "table") {
        var table = Tabulator.findTable("#"+this.renderDiv)[0];
        if (table !== undefined && this.axisX != null) {
          table.scrollToRow(parseInt(this.currentTime-this.currentTime%30));
        }
      }
    }
  }
};
