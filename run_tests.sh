#!/bin/bash

echo "Running tests..."

composer run dev --timeout=0

composer test
