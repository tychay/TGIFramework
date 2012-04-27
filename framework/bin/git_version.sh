#!/bin/bash
git_version()
{
   GITVERSION=`git describe`
   GITMODIFIED=`(git st | grep "modified:\|added:\|deleted:" -q) && echo "-M"`
   echo $GITVERSION$GITMODIFIED
}

