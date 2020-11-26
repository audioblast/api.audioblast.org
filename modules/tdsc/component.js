const tdscAB = {
  name: 'tdsc',
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
          this.dataRequested = fetch("https://api.audioblast.org/analysis/tdsc/?id="+this.id+"&source="+this.source+"&output=nakedJSON")
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
        generateAnalysisTabulator("#"+this.renderDiv, "tdsc", this.source, this.id, this.data, scrollTo);
      }
    },
    setCurrentTime: function(t) {
      this.currentTime = t;
      if (this.activeTab == "table") {
        var table = Tabulator.prototype.findTable("#"+this.renderDiv)[0];
        if (table !== undefined && this.axisX != null) {
          table.scrollToRow(parseInt(this.currentTime));
        }
      }
    },
    setAxisX: function(range) {
      if (range != null && !isNaN(range[0]) && !isNaN(range[1])) {
        this.axisX = range;
      }
    }
  }
};
