library(sonicscrewdriver)
library(jsonlite)

args <- commandArgs(trailingOnly=TRUE)
dt <- daysPhases(args[1], period=args[2])
js <- toJSON(dt)
cat(js)
