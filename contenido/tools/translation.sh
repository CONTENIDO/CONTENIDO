#!/bin/bash

# Creates or updates the translation files (*.pot) files for following areas
# - setup process
# - contenido core
# - plugins
#
# Usage:
# ------
# See function `help()` below.
#
# @package    Core
# @subpackage Tool
# @author     Murat Purc <murat@purc.de>
# @copyright  four for business AG <www.4fb.de>
# @license    https://www.contenido.org/license/LIZENZ.txt
# @link       https://www.4fb.de
# @link       https://www.contenido.org

# Some variables
SCRIPT_DIR=$(dirname $(realpath "$0"))
TRANSLATION_AREA="$1"
PLUGIN_NAME="$2"
TRANSLATION_DIR=""

# Allowed translation areas and their paths
declare -A TRANSLATION_PATHS=(
    [setup]=$(realpath "${SCRIPT_DIR}/../../setup")
    [contenido]=$(realpath "${SCRIPT_DIR}/..")
    [plugin]=$(realpath "${SCRIPT_DIR}/../plugins")
)
declare -A TRANSLATION_INFO=(
    [setup]="setup process"
    [contenido]="CONTENIDO core"
    [plugin]="plugin"
)

# Function to print help
help() {
    echo ""
    echo "Accepted translation areas are:"
    echo "- setup"
    echo "- contenido"
    echo "- plugin"
    echo ""
    echo "Usage:"
    echo "------"
    echo "$ ./translation.sh <area>"
    echo "$ ./translation.sh plugin <plugin_name>"
    echo ""
    echo "Examples:"
    echo "---------"
    echo "$ ./translation.sh setup"
    echo "$ ./translation.sh contenido"
    echo "$ ./translation.sh plugin content_allocation"
    echo "$ ./translation.sh plugin frontendlogic/category"
}

# Function to initialize and validate
initialize() {
    # Is translation area argument missing?
    if [ -z "$TRANSLATION_AREA" ]
    then
        echo "NOTICE >>> Missing translation area argument"
        help
        exit 1
    else
        # Is translation area argument valid?
        if ! [[ -v "TRANSLATION_PATHS[$TRANSLATION_AREA]" ]]
        then
            echo "NOTICE >>> Invalid translation area argument '$TRANSLATION_AREA'"
            help
            exit 1
        else
            # Is translation plugin name argument valid?
            if [ "$TRANSLATION_AREA" == "plugin" ]
            then
                if [ -z "$PLUGIN_NAME" ]
                then
                    echo "NOTICE >>> Missing plugin name argument"
                    help
                    exit 1
                fi
            fi
        fi
    fi

    # Set translation directory
    if [ "$TRANSLATION_AREA" == "plugin" ]
    then
        TRANSLATION_DIR="${TRANSLATION_PATHS[$TRANSLATION_AREA]}/$PLUGIN_NAME"
    else
        TRANSLATION_DIR="${TRANSLATION_PATHS[$TRANSLATION_AREA]}"
    fi

    # Check if translation directory exists
    if [ ! -d "$TRANSLATION_DIR" ]
    then
        echo "NOTICE >>> Translation directory '$TRANSLATION_DIR' does not exist."
        if [ "$TRANSLATION_AREA" == "plugin" ]
        then
            echo "NOTICE >>> Could not find plugin folder '$PLUGIN_NAME'."
        fi
        exit 1
    fi
}


# Function to process the translation...
translate() {
    cd $TRANSLATION_DIR

    if [ "$TRANSLATION_AREA" == "plugin" ]
    then
        echo "INFO >>> Generating translation for plugin '$PLUGIN_NAME'..."
    else
        echo "INFO >>> Generating translation for ${TRANSLATION_INFO[$TRANSLATION_AREA]}..."
    fi

    case $TRANSLATION_AREA in
        "setup")
            find . -iname "*.php" -o -iname "*.html" -o -iname "*.xml" -o -iname "*.tpl"  \
              > ./locale/potfiles.txt
            xgettext --from-code=utf-8 --keyword=i18n --output=./locale/setup.pot \
              --files-from=./locale/potfiles.txt
            ;;
        "contenido")
            find . ../data/config -path "./plugins" -prune ! -type d -o -path "./jar" -prune \
              ! -type d -o -path "./external/codemirror" -prune ! -type d \
              -o -iname "*.php" -o -iname "*.html" -o -iname "*.xml" -o -iname "*.tpl" \
              > ../data/locale/potfiles.txt
            xgettext --from-code=utf-8 --keyword=i18n --output=../data/locale/contenido.pot \
              --files-from=../data/locale/potfiles.txt
            ;;
        "plugin")
            PLUGIN_FILE_NAME="${PLUGIN_NAME////_}"
            find . -iname "*.php" -o -iname "*.html" -o -iname "*.xml" -o -iname "*.tpl" \
              > ./locale/potfiles.txt
            xgettext --from-code=utf-8 --keyword=i18n --output=./locale/${PLUGIN_FILE_NAME}.pot \
              --files-from=./locale/potfiles.txt
            ;;
    esac
    echo "INFO >>> Translation finished."
}

# The main entry point
main() {
    initialize
    translate
}

# Call the main function
main