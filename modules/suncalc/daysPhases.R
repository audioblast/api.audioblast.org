library(sonicscrewdriver)
library(jsonlite)

args <- commandArgs(trailingOnly=TRUE)
dt <- daysPhases(date=args[1], period=args[2], lat=as.numeric(args[3]), lon=as.numeric(args[4]), tz=args[5])
js <- toJSON(dt)
cat(js)
