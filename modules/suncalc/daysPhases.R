library(sonicscrewdriver)
library(jsonlite)

args <- commandArgs(trailingOnly=TRUE)
dt <- daysPhases(args[1], period=args[2], lat=args[3], lon=args[4], tz=args[5])
js <- toJSON(dt)
cat(js)
