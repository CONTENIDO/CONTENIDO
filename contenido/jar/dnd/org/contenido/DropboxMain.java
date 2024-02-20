package org.contenido;

import java.applet.*;
import java.awt.BorderLayout;
import java.awt.Color;
import java.awt.Container;
import java.awt.Graphics;
import java.awt.datatransfer.DataFlavor;
import java.awt.datatransfer.Transferable;
import java.awt.datatransfer.UnsupportedFlavorException;
import java.awt.dnd.DropTarget;
import java.awt.dnd.DropTargetDragEvent;
import java.awt.dnd.DropTargetDropEvent;
import java.awt.dnd.DropTargetEvent;
import java.awt.dnd.DropTargetListener;
import java.io.File;
import java.io.IOException;
import java.util.List;
import java.util.TooManyListenersException;

import javax.swing.JFrame;
import javax.swing.JLabel;

/**
 * The Main class of the applet. It listens to drop events of the Dropbox-label and handles all the components.
 *
 *
 * @author Mischa Holz
 *
 * @version 1.1, 01/13/2012
 *
 * @since 1.0
 */

public class DropboxMain extends Applet implements DropTargetListener {
	public static int barHeight = 85;

	public static int appletWidth = 500;
	public static int appletHeight = 100;

	public static int dropAreaWidth = 500;
	public static int dropAreaHeight = 100;
	public static int dropAreaX = 0;
	public static int dropAreaY = 0;

	public static int shelfWidth = 500;
	public static int shelfX = 0;
	public static int shelfY = dropAreaY + dropAreaHeight;

	public static int progressBarLeftOffset = 20;
	public static int progressBarRightOffset = 20;
	public static int progressBarWidth = shelfWidth - (progressBarLeftOffset + progressBarRightOffset);
	public static int progressBarHeight = 20;
	public static int progressBarX = progressBarLeftOffset;
	public static int progressBarY = (barHeight / 2) - (progressBarHeight / 2);

	public static int cancelButtonWidth = 80;
	public static int cancelButtonHeight = 20;
	public static int cancelButtonX = (progressBarX + progressBarWidth) - cancelButtonWidth;
	public static int cancelButtonY = progressBarY + progressBarHeight + 5;

	public static int barTitleLabelX = progressBarX;
	public static int barTitleLabelY = 5;
	public static int barTitleLabelWidth = shelfWidth - barTitleLabelX;
	public static int barTitleLabelHeight = 25;

	public static int barStatusLabelX = progressBarX;
	public static int barStatusLabelY = progressBarY + progressBarHeight + 5;
	public static int barStatusLabelWidth = cancelButtonX - barStatusLabelX;
	public static int barStatusLabelHeight = 25;

	private static final long serialVersionUID = 1L;

	private static String[] measures = { "bytes", "KB", "MB", "GB", "TB", "PT", "ET" };

	/**
	 * Converts bytes into a human readable string.
	 *
	 * @param bytes - the number of bytes
	 * @return The String in the form of "xx bytes/KB/MB/GB/TB/PT/ET"
	 */
	public static String bytesForHuman(long bytes) {
		int exp = 0;
		long threshold = 1024;
		for(int i = 0; i < 7; i++) {
			if(bytes < threshold) {
				exp = i;
				break;
			}
			threshold *= 1024;
		}

		int rel = (int)(bytes / (threshold / 1024));
		return Integer.toString(rel) + " " + measures[exp];
	}

	private JLabel text;
	private UploadStatusShelf progress;
	private boolean firstUpload;
	private JFrame jfProgress;

	/**
	 * Assigns the necessary events and creates the components.
	 */
	public void init() {
		firstUpload = false;
		this.setBackground(Color.white);
		this.setBounds(0, 0, appletWidth, appletHeight);

		setLayout(new BorderLayout());
		Container main = new Container();
		main.setBounds(0, 0,appletWidth, appletHeight);
		add(main);

		text = new Dropbox();
		DropTarget dt = new DropTarget();
		dt.setComponent(text);
		try {
			dt.addDropTargetListener(this);
		} catch (TooManyListenersException e) {
			e.printStackTrace();
		}
		main.add(text);

		progress = new UploadStatusShelf(this, this.getParameter("uid"), this.getParameter("host"), this.getParameter("upload_path"));
	}

	/**
	 * Updates the components of the applet and sets the correct size by calling UploadStatusSehlf.<!-- -->updateSize().
	 *
	 * @see UploadStatusShelf
	 */
	public void update(Graphics g) {
		progress.updateSize();

		paint(g);
	}

	/**
	 * Implemented nop-method for dragEnter events because they are not needed by this applet.
	 */
	public void dragEnter(DropTargetDragEvent dtde) {

	}

	/**
	 * Implemented nop-method for dragExit events because they are not needed by this applet.
	 */
	public void dragExit(DropTargetEvent dte) {

	}

	/**
	 * Implemented nop-method for dragOver events because they are not needed by this applet.
	 */
	public void dragOver(DropTargetDragEvent dtde) {

	}

	/**
	 * Takes the drop events and, if they were any files dropped, adds them to the upload shelf.
	 *
	 * @param dtde	the events' information
	 *
	 * @see UploadStatusShelf
	 */
	public void drop(DropTargetDropEvent dtde) {

		int action = dtde.getDropAction();
		dtde.acceptDrop(action);

		Transferable t = dtde.getTransferable();
		if(t.isDataFlavorSupported(DataFlavor.javaFileListFlavor)) {
			try {
				@SuppressWarnings("unchecked")
				List<File> fileList = (List<File>) t.getTransferData(DataFlavor.javaFileListFlavor);
				for(int i = 0; i < fileList.size(); i++) {
					File f = fileList.get(i);
					if(f.isDirectory()) {
						return;
					}

					progress.addNewUpload(f);
					if(!firstUpload) {
						firstUpload = true;
						jfProgress = new JFrame();
						jfProgress.add(progress);
						jfProgress.setSize(progress.getSize());
						jfProgress.setVisible(true);
					}

					this.repaint();
					dtde.dropComplete(true);
				}
			} catch (UnsupportedFlavorException e) {
				e.printStackTrace();
			} catch (IOException e) {
				e.printStackTrace();
			}
		}
	}

	/**
	 * Implemented nop-method for dropActionChanged events because they are not needed by this applet.
	 */
	public void dropActionChanged(DropTargetDragEvent dtde) {

	}
}
