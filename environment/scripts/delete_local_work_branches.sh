#!/usr/bin/env bash
  
###############################################################
#
# This script will delete all branches from your local git
# workspaces except for test, and master branches
#
################################################################
# Get the list of local branches
BRANCHES=`git branch --format='%(refname:short)'`

# loop through the branches
for branch in $BRANCHES;
do
    # check if the branch is not test or master as
    # we do not want to delete those branches
    if [ "$branch" != "develop" ] && [ "$branch" != "master" ] && [ "$branch" != "release-candidate" ] && [ "$branch" != "staging" ] && [ "$branch" != "test" ]
    then
        git branch -D $branch
    fi
done

