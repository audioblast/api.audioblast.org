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
    },
    doRender: function() {
      if (this.activeTab == "table") {
        generateAnalysisTabulator("#"+this.renderDiv, "tdsc", this.source, this.id, null, scrollTo);
      }
    },
    setCurrentTime: function(t) {
      this.currentTime = t;
      if (this.activeTab == "table") {
        var table = Tabulator.findTable("#"+this.renderDiv)[0];
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
